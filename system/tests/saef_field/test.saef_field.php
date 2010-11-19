<?php

/**
 * SAEF Field tests.
 *
 * @author			Stephen Lewis <addons@experienceinternet.co.uk>
 * @copyright		Experience Internet
 * @package			SAEF Field
 */

require_once PATH .'tests/saef_field/mocks/mock.saef_field_model' .EXT;
require_once PATH_PI .'pi.saef_field' .EXT;

class Test_saef_field extends Testee_unit_test_case {
	
	/* --------------------------------------------------------------
	 * PRIVATE PROPERTIES
	 * ------------------------------------------------------------ */
	
	/**
	 * Mock model.
	 *
	 * @access	private
	 * @var		object
	 */
	private $_model;
	
	/**
	 * Plugin.
	 *
	 * @access	private
	 * @var		object
	 */
	private $_pi;
	
	
	
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
		parent::setUp();
		
		// Mock model.
		Mock::generate('Mock_saef_field_model', 'Mock_model');
		
		$this->_model	= new Mock_model();
		$this->_pi 		= new Saef_field();
		
		$this->_pi->set_mock_model($this->_model);
	}
	
	
	
	/* --------------------------------------------------------------
	 * TEST METHODS
	 * ------------------------------------------------------------ */
	
	public function test_swap_single_vars__success()
	{
		global $TMPL;
		
		// Dummy values.
		$field_fmt			= 'none';
		$field_id			= '10';
		$field_instructions	= 'Complete this field.';
		$field_label		= 'Field Label';
		$field_name			= 'field_name';
		$field_required		= 'y';
		$field_type			= 'text';
		
		$tagdata = "<dl>
			<dt>Formatting:</dt><dd>{field_fmt}</dd>
			<dt>ID:</dt><dd>{field_id}</dd>
			<dt>Instructions:</dt><dd>{field_instructions}</dd>
			<dt>Label:</dt><dd>{field_label}</dd>
			<dt>Type:</dt><dd>{field_type}</dd>
			</dl>";
		
		$return_data = "<dl>
			<dt>Formatting:</dt><dd>{$field_fmt}</dd>
			<dt>ID:</dt><dd>{$field_id}</dd>
			<dt>Instructions:</dt><dd>{$field_instructions}</dd>
			<dt>Label:</dt><dd>{$field_label}</dd>
			<dt>Type:</dt><dd>{$field_type}</dd>
			</dl>";
		
		$field_data = array(
			'field_fmt'				=> $field_fmt,
			'field_id'				=> $field_id,
			'field_instructions'	=> $field_instructions,
			'field_label'			=> $field_label,
			'field_list_items'		=> array('Yes', 'No'),
			'field_name'			=> $field_name,
			'field_related_id'		=> 10,
			'field_required'		=> $field_required,
			'field_type'			=> $field_type
		);
		
		$template_var_single = array('field_fmt', 'field_id', 'field_instructions', 'field_label', 'field_name', 'field_type');
		
		// Expectations.
		$single_vars_count = 0;
		$swap_tagdata = $tagdata;
		
		foreach ($field_data AS $tag_name => $tag_value)
		{
			if (is_string($tag_value) OR is_numeric($tag_value))
			{
				$TMPL->expectAt($single_vars_count, 'swap_var_single', array($tag_name, $tag_value, $swap_tagdata));
			
				if (in_array($tag_name, $template_var_single))
				{
					$swap_tagdata = str_replace(LD .$tag_name .RD, $field_data[$tag_name], $swap_tagdata);
				}
			
				$TMPL->setReturnValueAt($single_vars_count, 'swap_var_single', $swap_tagdata);
			
				$single_vars_count++;
			}
		}
		
		// Return values.
		$TMPL->setReturnValue('__get', $template_var_single, array('var_single'));
		
		// Tests.
		$this->assertIdentical($return_data, $this->_pi->swap_single_vars($tagdata, $field_data));
	}
	
	
	public function test_swap_single_vars__no_vars()
	{
		global $TMPL;
		
		// Dummy values.
		$tagdata 	= '<p>Wibble</p>';
		$field_data	= array();
		
		// Expectations.
		$TMPL->expectNever('__get');
		$TMPL->expectNever('swap_var_single');
		
		// Tests.
		$this->assertIdentical($tagdata, $this->_pi->swap_single_vars($tagdata, $field_data));
	}
	
	
	public function test_swap_single_vars__no_tagdata()
	{
		global $TMPL;
		
		// Dummy values.
		$tagdata 	= '';
		$field_data	= array('dummy_var' => 'Dummy');
		
		// Expectations.
		$TMPL->expectNever('__get');
		$TMPL->expectNever('swap_var_single');
		
		// Tests.
		$this->assertIdentical($tagdata, $this->_pi->swap_single_vars($tagdata, $field_data));
	}
	
}


/* End of file		: test.saef_field.php */
/* File location	: system/tests/saef_field/test.saef_field.php */