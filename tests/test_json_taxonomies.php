<?php

/**
 * Unit tests covering WP_JSON_Taxonomies functionality.
 *
 * @package WordPress
 * @subpackage JSON API
 */
class WP_Test_JSON_Taxonomies extends WP_UnitTestCase {
	/**
	 * This function is run before each method
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create();
		wp_set_current_user( $this->user );

		$this->fake_server = $this->getMock('WP_JSON_Server');
		$this->endpoint = new WP_JSON_Taxonomies( $this->fake_server );
	}

	/**
	 * Utility function for use in get_public_taxonomies
	 */
	private function is_public( $taxonomy ) {
		return $taxonomy->public !== false;
	}

	/**
	 * Utility function to filter down to only public taxonomies
	 */
	private function get_public_taxonomies( $taxonomies ) {
		// Pass through array_values to re-index after filtering
		return array_values( array_filter( $taxonomies, array( $this, 'is_public' ) ) );
	}

	public function test_get_taxonomies() {
		$response = $this->endpoint->get_taxonomies();
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = json_ensure_response( $response );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$taxonomies = $this->get_public_taxonomies( get_taxonomies( '', 'objects' ) );
		$this->assertEquals( count( $taxonomies ), count( $data ) );

		// Check each key in $data against those in $taxonomies
		foreach ( array_keys( $data ) as $key ) {
			$this->assertEquals( $taxonomies[$key]->label, $data[$key]['name'] );
			$this->assertEquals( $taxonomies[$key]->name, $data[$key]['slug'] );
			$this->assertEquals( $taxonomies[$key]->hierarchical, $data[$key]['hierarchical'] );
			$this->assertEquals( $taxonomies[$key]->show_tagcloud, $data[$key]['show_cloud'] );
		}
	}

	public function test_get_taxonomies_with_types() {
		foreach ( get_post_types() as $type ) {
			$response = $this->endpoint->get_taxonomies( $type );
			$this->assertNotInstanceOf( 'WP_Error', $response );
			$response = json_ensure_response( $response );

			$this->assertEquals( 200, $response->get_status() );

			$data = $response->get_data();

			$taxonomies = $this->get_public_taxonomies( get_object_taxonomies( $type, 'objects' ) );

			$this->assertEquals( count( $taxonomies ), count( $data ) );
		}
	}

	public function test_get_taxonomy() {
		$response = $this->endpoint->get_taxonomy( 'category' );
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = json_ensure_response( $response );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$category = get_taxonomy( 'category' );

		$this->assertEquals( $category->label, $data['name'] );
		$this->assertEquals( $category->name, $data['slug'] );
		$this->assertEquals( $category->show_tagcloud, $data['show_cloud'] );
		$this->assertEquals( $category->hierarchical, $data['hierarchical'] );
	}

	public function test_get_terms() {
		$response = $this->endpoint->get_terms( 'category' );
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = json_ensure_response( $response );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$args = array(
			'hide_empty' => false,
		);
		$categories = get_terms( 'category', $args );

		$this->assertEquals( count( $categories ), count( $data ) );

		$this->assertEquals( $categories[0]->term_id, $data[0]['ID'] );
		$this->assertEquals( $categories[0]->name, $data[0]['name'] );
		$this->assertEquals( $categories[0]->slug, $data[0]['slug']);
		$this->assertEquals( $categories[0]->description, $data[0]['description']);
		$this->assertEquals( $categories[0]->count, $data[0]['count']);
	}

	public function test_get_term() {
		$response = $this->endpoint->get_term( 'category', 1 );
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = json_ensure_response( $response );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$category = get_term( 1, 'category' );

		$this->assertEquals( $category->term_id, $data['ID'] );
		$this->assertEquals( $category->name, $data['name'] );
		$this->assertEquals( $category->slug, $data['slug'] );
		$this->assertEquals( $category->description, $data['description'] );
		$this->assertEquals( $category->count, $data['count'] );
	}
}
