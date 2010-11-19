<?php

/**
 * Makes working with Stand-alone Entry Forms marginally less unpleasant.
 *
 * @author			Stephen Lewis <addons@experienceinternet.co.uk>
 * @copyright		Experience Internet
 * @package			SAEF Field
 */

require_once PATH_PI .'saef_field/saef_field_model' .EXT;

class Saef_field {
	
	/* --------------------------------------------------------------
	 * PRIVATE PROPERTIES
	 * ------------------------------------------------------------ */
	
	/**
	 * Model.
	 *
	 * @access	private
	 * @var		Saef_field_model
	 */
	private $_model;
	
	
	
	/* --------------------------------------------------------------
	 * PUBLIC PROPERTIES
	 * ------------------------------------------------------------ */
	
	/**
	 * Plugin information.
	 *
	 * @access	public
	 * @var		array
	 */
	public $plugin_info;
	
	
	
	/* --------------------------------------------------------------
	 * PUBLIC METHODS
	 * ------------------------------------------------------------ */
	
	/**
	 * Constructor.
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		$this->_model = new Saef_field_model();
		
		$this->plugin_info = array(
			'pi_author'			=> 'Stephen Lewis',
			'pi_author_url'		=> 'http://experienceinternet.co.uk/software/saef_field/',
			'pi_description'	=> 'Makes working with SAEFs marginally less unpleasant.',
			'pi_name'			=> 'SAEF Field',
			'pi_usage'			=> $this->_model->get_usage_instructions(),
			'pi_version'		=> $this->_model->get_package_version()
		);
	}
	
	
	/**
	 * Outputs information for the specified field.
	 *
	 * @access	public
	 * @return	string
	 */
	public function field()
	{
		global $TMPL;
		
		if ( ! $short_names = $TMPL->fetch_param('short_name'))
		{
			return $TMPL->no_results();
		}
		
		$short_names 	= explode('|', $short_names);
		$fields			= array();
		
		foreach ($short_names AS $short_name)
		{
			$fields[] = $this->_model->get_field_from_short_name($short_name);
		}
		
		if ( ! $fields)
		{
			return $TMPL->no_results();
		}
		
		$tagdata 		= $TMPL->tagdata;
		$return_tagdata	= '';
		
		foreach ($fields AS $field)
		{
			$loop_tagdata	= $tagdata;
			$entry_data 	= $this->_model->get_weblog_entry_field_data(
				$TMPL->fetch_param('entry_id'), array($field['field_id'])
			);
			
			/**
			 * Add the current field value to the field data array. If no entry_id was
			 * specified, or we have been unable to retrieve the current field value,
			 * just assume it is empty.
			 */

			$field['field_value'] = $entry_data
				? $entry_data['field_id_' .$field['field_id']]
				: '';
			
			$loop_tagdata	= $this->swap_pair_vars($this->swap_single_vars($loop_tagdata, $field), $field);
			$return_tagdata .= $loop_tagdata;
		}
		
		return $return_tagdata;
	}
	
	
	/**
	 * Swaps the pair variables in the supplied tagdata.
	 *
	 * @access	public
	 * @param	string		$tagdata		The tagdata.
	 * @param	array		$field_data		The field data.
	 * @return	string
	 */
	public function swap_pair_vars($tagdata, Array $field_data = array())
	{
		global $TMPL;
		
		// Get out early.
		if ( ! $field_data OR ! $tagdata OR ! is_string($tagdata))
		{
			return $tagdata;
		}
		
		/**
		 * TRICKY:
		 * Using the SLASH constant in a regular expression throws an error. Using the '/'
		 * character results in no matches.
		 *
		 * The solution is to replace all the SLASHes with '/', and then swap them
		 * back after running the regular expression.
		 */
		
		$tagdata = str_replace(SLASH, '/', $tagdata);
		
		// We only care about the {field_options} tag pair, which accepts no parameters.
		$field_options_pattern	= '#' .LD .'field_options' .RD .'(.*?)' .LD .'/field_options' .RD .'#s';
		$field_options			= '';
		
		preg_match_all($field_options_pattern, $tagdata, $matches, PREG_PATTERN_ORDER);
		
		foreach ($matches[1] AS $inner_tagdata)
		{
			// Retrieve the data between the tag pair.
			// $inner_tagdata = $TMPL->fetch_data_between_var_pairs($tagdata, $tag_name);
			
			/**
			 * 'select' field type.
			 */
			
			if ($field_data['field_type'] == 'select')
			{
				foreach ($field_data['field_list_items'] AS $field_list_item)
				{
					$single_vars = array(
						'option_selected'	=> $field_data['field_value'] === $field_list_item ? 'checked="checked"' : '',
						'option_label' 		=> $field_list_item,
						'option_value' 		=> $field_list_item,
						'option_selected'	=> $field_data['field_value'] === $field_list_item ? 'selected="selected"' : ''
					);
					
					$field_options .= $this->swap_single_vars($inner_tagdata, $single_vars);
				}
			}
			
			/**
			 * 'rel' field type.
			 */
			
			if ($field_data['field_type'] == 'rel')
			{
				if ($relationship_data = $this->_model->get_relationship_data($field_data['field_value']))
				{
					$field_data['field_value'] = $relationship_data['rel_child_id'];
				}
				
				$related_entries = $this->_model->get_weblog_entries($field_data['field_related_id'], $this->_model->get_site_id());
				
				foreach ($related_entries AS $related_entry)
				{
					$single_vars = array(
						'option_checked'	=> $field_data['field_value'] === $related_entry['entry_id'] ? 'checked="checked"' : '',
						'option_label' 		=> $related_entry['title'],
						'option_value' 		=> $related_entry['entry_id'],
						'option_selected'	=> $field_data['field_value'] === $related_entry['entry_id'] ? 'selected="selected"' : ''
					);
					
					$field_options .= $this->swap_single_vars($inner_tagdata, $single_vars);
				}
			}
		}
		
		// Replace the {field_options} tag pair with the $field_options data.
		return str_replace('/', SLASH, preg_replace($field_options_pattern, $field_options, $tagdata));
	}
	
	
	/**
	 * Swaps the single variables in the supplied tagdata.
	 *
	 * @access	public
	 * @param	string		$tagdata		The tagdata.
	 * @param	array		$field_data		The field data.
	 * @return	string
	 */
	public function swap_single_vars($tagdata, Array $field_data = array())
	{
		global $FNS, $TMPL;
		
		// Get out early.
		if ( ! $field_data OR ! $tagdata OR ! is_string($tagdata))
		{
			return $tagdata;
		}
		
		$tagdata = $FNS->prep_conditionals($tagdata, $field_data);
		
		/**
		 * IMPORTANT:
		 * This works fine for our simple purposes. If we needed to support
		 * tag parameters (such as date formatting), it would all fall over.
		 *
		 * At that point, we'd need to loop through the $TMPL->var_single
		 * array instead, and would no longer be able to call this method
		 * from $this->swap_pair_vars.
		 */
		
		foreach ($field_data AS $tag_name => $tag_value)
		{
			if (is_string($tag_value) OR is_numeric($tag_value))
			{
				$tagdata = $TMPL->swap_var_single($tag_name, $tag_value, $tagdata);
			}
		}
		
		return $tagdata;
	}
	
	
	/**
	 * Sets the mock model. Used for testing.
	 *
	 * @access	public
	 * @param	object		$model		The mock model.
	 * @return	void
	 */
	public function set_mock_model($model)
	{
		$this->_model = $model;
	}
	
}


/* End of file		: pi.saef_field.php */
/* File location	: system/plugins/pi.saef_field.php */