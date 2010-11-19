<?php

/**
 * SAEF Field Model tests.
 *
 * @author			Stephen Lewis <addons@experienceinternet.co.uk>
 * @copyright		Experience Internet
 * @package			SAEF Field
 */

require_once PATH_PI .'saef_field/saef_field_model' .EXT;

class Test_saef_field_model extends Testee_unit_test_case {
	
	/* --------------------------------------------------------------
	 * PRIVATE PROPERTIES
	 * ------------------------------------------------------------ */
	
	/**
	 * Model.
	 *
	 * @access	private
	 * @var		object
	 */
	private $_model;
	
	
	
	/* --------------------------------------------------------------
	 * PUBLIC METHODS
	 * ------------------------------------------------------------ */
	
	/**
	 * Initialisation.
	 *
	 * @access	public
	 * @return	void
	 */
	public function setUp()
	{
		global $PREFS;
		
		parent::setUp();
		
		// Called frequently, so we just mock it here.
		$PREFS->setReturnValue('ini', 1, array('site_id'));
		
		// Create the model.
		$this->_model = new Saef_field_model();
	}
	
	
	
	/* --------------------------------------------------------------
	 * TEST METHODS
	 * ------------------------------------------------------------ */
	public function test_get_field_from_short_name__success()
	{
		global $DB;
		
		// Dummy values.
		$short_name	= 'field_name';
		$db_result 	= $this->_get_mock('db_cache');
		$db_row		= array(
			'field_fmt'				=> 'xhtml',
			'field_id'				=> '10',
			'field_instructions'	=> 'Complete this field.',
			'field_label'			=> 'Field Label',
			'field_list_items'		=> "a\nb\nc",
			'field_name'			=> $short_name,
			'field_related_id'		=> 10,
			'field_required'		=> 'y',
			'field_type'			=> 'text'
		);
		
		$return_data 						= $db_row;
		$return_data['field_list_items']	= array('a', 'b', 'c');
		$return_data['field_required']		= TRUE;
		
		$sql = "SELECT field_fmt, field_id, field_instructions, field_label, field_list_items, field_name,
				field_related_id, field_required, field_type
			FROM exp_weblog_fields
			WHERE field_name = '{$short_name}'
			LIMIT 1";
		
		// Expectations.
		$DB->expectOnce('query', array(new EqualWithoutWhitespaceExpectation($sql)));
		
		// Return values.
		$DB->setReturnReference('query', $db_result);
		$db_result->setReturnValue('__get', 1, array('num_rows'));
		$db_result->setReturnValue('__get', $db_row, array('row'));
		
		// Tests.
		$this->assertIdentical($return_data, $this->_model->get_field_from_short_name($short_name));
	}
	
	
	public function test_get_field_from_short_name__no_result()
	{
		global $DB;
		
		// Dummy values.
		$short_name	= 'field_name';
		$db_result 	= $this->_get_mock('db_cache');
		
		// Return values.
		$DB->setReturnReference('query', $db_result);
		$db_result->setReturnValue('__get', 0, array('num_rows'));
		
		// Tests.
		$this->assertIdentical(FALSE, $this->_model->get_field_from_short_name($short_name));
	}
	
	
	public function test_get_field_from_short_name__no_short_name()
	{
		global $DB;
		
		// Expectations.
		$DB->expectNever('query');
		
		// Tests.
		$this->assertIdentical(FALSE, $this->_model->get_field_from_short_name(''));
	}
	
	
	public function test_get_weblog_entries__success()
	{
		global $DB;
		
		// Dummy values.
		$db_result = $this->_get_mock('db_cache');
		$db_rows = array(
			array('entry_id' => '10', 'title' => 'Example Weblog Entry A'),
			array('entry_id' => '20', 'title' => 'Example Weblog Entry B')
		);
		
		$weblog_id 	= 5;
		$site_id	= 10;
		$sql 		= "SELECT entry_id, title
			FROM exp_weblog_titles
			WHERE site_id = '{$site_id}' AND weblog_id = '{$weblog_id}'
			ORDER BY title ASC";
		
		// Expectations.
		$DB->expectOnce('query', array(new EqualWithoutWhitespaceExpectation($sql)));
		
		// Return values.
		$DB->setReturnReference('query', $db_result);
		$db_result->setReturnValue('__get', count($db_rows), array('num_rows'));
		$db_result->setReturnValue('__get', $db_rows, array('result'));
		
		// Tests.
		$this->assertIdentical($db_rows, $this->_model->get_weblog_entries($weblog_id, $site_id));
	}
	
	
	public function test_get_weblog_entries__no_entries()
	{
		global $DB;
		
		// Dummy values.
		$db_result 	= $this->_get_mock('db_cache');
		$weblog_id 	= 5;
		$site_id	= 10;
		
		// Expectations.
		$DB->expectOnce('query');
		
		// Return values.
		$DB->setReturnReference('query', $db_result);
		$db_result->setReturnValue('__get', 0, array('num_rows'));
		
		// Tests.
		$this->assertIdentical(array(), $this->_model->get_weblog_entries($weblog_id, $site_id));
	}
	
	
	public function test_get_weblog_entries__no_weblog_id()
	{
		global $DB;
		
		// Dummy values.
		$db_result 	= $this->_get_mock('db_cache');
		$weblog_id 	= NULL;
		$site_id	= 10;
		
		// Expectations.
		$DB->expectNever('query');
		
		// Tests.
		$this->assertIdentical(array(), $this->_model->get_weblog_entries($weblog_id, $site_id));
	}
	
	
	public function test_get_weblog_entries__no_site_id()
	{
		global $DB;
		
		// Dummy values.
		$db_result 	= $this->_get_mock('db_cache');
		$weblog_id 	= 10;
		$site_id	= NULL;
		
		// Expectations.
		$DB->expectNever('query');
		
		// Tests.
		$this->assertIdentical(array(), $this->_model->get_weblog_entries($weblog_id, $site_id));
	}
	
	
	public function test_get_weblog_entry_field_data__success()
	{
		global $DB;
		
		// Dummy values.
		$entry_id	= 10;
		$field_ids	= array(5, 10, 15, 'field_id_20');
		$sql		= "SELECT field_id_5, field_id_10, field_id_15, field_id_20
			FROM exp_weblog_data WHERE entry_id = '{$entry_id}' LIMIT 1";
		
		$db_result	= $this->_get_mock('db_cache');
		$db_row		= array(
			'field_id_5' => 'Field value A',
			'field_id_10' => 'Field value B',
			'field_id_15' => 'Field value C',
			'field_id_20' => 'Field value D'
		);
		
		// Expectations.
		$DB->expectOnce('query', array(new EqualWithoutWhitespaceExpectation($sql)));
		
		// Return values.
		$DB->setReturnReference('query', $db_result);
		$db_result->setReturnValue('__get', 1, array('num_rows'));
		$db_result->setReturnValue('__get', $db_row, array('row'));
		
		// Tests.
		$this->assertIdentical($db_row, $this->_model->get_weblog_entry_field_data($entry_id, $field_ids));
	}
	
	
	public function test_get_weblog_entry_field_data__no_entry()
	{
		global $DB;
		
		// Dummy values.
		$entry_id	= 10;
		$field_ids	= array(5, 10, 15, 20);
		$db_result	= $this->_get_mock('db_cache');
		
		// Return values.
		$DB->setReturnReference('query', $db_result);
		$db_result->setReturnValue('__get', 0, array('num_rows'));
		
		// Tests.
		$this->assertIdentical(FALSE, $this->_model->get_weblog_entry_field_data($entry_id, $field_ids));
	}
	
	
	public function test_get_weblog_entry_field_data__no_entry_id()
	{
		global $DB;
		
		// Dummy values.
		$entry_id	= 0;
		$field_ids	= array(5, 10, 15, 20);
		
		// Expectations.
		$DB->expectNever('query');
		
		// Tests.
		$this->assertIdentical(FALSE, $this->_model->get_weblog_entry_field_data($entry_id, $field_ids));
	}
	
	
	public function test_get_weblog_entry_field_data__no_fields()
	{
		global $DB;
		
		// Dummy values.
		$entry_id	= 10;
		$field_ids	= array();
		
		// Expectations.
		$DB->expectNever('query');
		
		// Tests.
		$this->assertIdentical(FALSE, $this->_model->get_weblog_entry_field_data($entry_id, $field_ids));
	}
	
	
	public function test_get_relationship_data__success()
	{
		global $DB;
		
		// Dummy values.
		$rel_id 	= 10;
		$db_result	= $this->_get_mock('db_cache');
		$db_row 	= array(
			'rel_child_id'		=> 5,
			'rel_data'			=> '',
			'rel_id' 			=> $rel_id,
			'rel_parent_id'		=> 20,
			'rel_type'			=> 'blog',
			'reverse_rel_data'	=> ''
		);
		
		$sql = "SELECT rel_child_id, rel_data, rel_id, rel_parent_id, rel_type, reverse_rel_data
			FROM exp_relationships
			WHERE rel_id = '{$rel_id}'
			LIMIT 1";
		
		// Expectations.
		$DB->expectOnce('query', array(new EqualWithoutWhitespaceExpectation($sql)));
		
		// Return values.
		$DB->setReturnReference('query', $db_result);
		$db_result->setReturnValue('__get', 1, array('num_rows'));
		$db_result->setReturnValue('__get', $db_row, array('row'));
		
		// Tests.
		$this->assertIdentical($db_row, $this->_model->get_relationship_data($rel_id));
	}
	
	
	public function test_get_relationship_data__no_result()
	{
		global $DB;
		
		// Dummy values.
		$rel_id 	= 10;
		$db_result	= $this->_get_mock('db_cache');
		
		// Return values.
		$DB->setReturnReference('query', $db_result);
		$db_result->setReturnValue('__get', 0, array('num_rows'));
		
		// Tests.
		$this->assertIdentical(FALSE, $this->_model->get_relationship_data($rel_id));
	}
	
	
	public function test_get_relationship_data__no_rel_id()
	{
		global $DB;
		
		// Dummy values.
		$rel_id = NULL;
		
		// Expectations.
		$DB->expectNever('query');
		
		// Tests.
		$this->assertIdentical(FALSE, $this->_model->get_relationship_data($rel_id));
	}
	
}


/* End of file		: test.saef_field_model.php */
/* File location	: system/tests/saef_field/test.saef_field_model.php */