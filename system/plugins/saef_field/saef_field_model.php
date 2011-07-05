<?php

/**
 * Makes working with Stand-alone Entry Forms marginally less unpleasant.
 *
 * @author          Stephen Lewis (http://github.com/experience/)
 * @copyright       Experience Internet
 * @package         SAEF Field
 * @version         1.0.1
 */

class Saef_field_model {
    
    private $_package_name;
    private $_site_id;
    
    
    
    /* --------------------------------------------------------------
     * STATIC METHODS
     * ------------------------------------------------------------ */
    
    /**
     * Returns the package version.
     *
     * @static
     * @access  public
     * @return  string
     */
    public static function get_package_version()
    {
        return '1.0.1';
    }


    /**
     * Returns the plugin usage instructions.
     *
     * @static
     * @access  public
     * @return  string
     */
    public static function get_usage_instructions()
    {
        return 'http://experienceinternet.co.uk/software/saef-field/docs/';
    }



    /* --------------------------------------------------------------
     * PUBLIC METHODS
     * ------------------------------------------------------------ */
    
    /**
     * Constructor.
     *
     * @access  public
     * @return  void
     */
    public function __construct()
    {
        $this->_package_name    = 'Saef_field';
    }
    
    
    /**
     * Retrieves all the required information for the specified field.
     *
     * @access  public
     * @param   string      $short_name     The field 'short name'.
     * @return  array|bool
     */
    public function get_field_from_short_name($short_name)
    {
        global $DB;
        
        // Get out early.
        if ( ! is_string($short_name) OR $short_name == '')
        {
            return FALSE;
        }
        
        $db_field = $DB->query("SELECT
                field_fmt, field_id, field_instructions, field_label, field_list_items,
                field_name, field_related_id, field_required, field_type
            FROM exp_weblog_fields
            WHERE field_name = '{$short_name}'
            LIMIT 1");
        
        if ( ! $db_field->num_rows)
        {
            return FALSE;
        }
        
        // Spruce up the return data.
        $return_data                        = $db_field->row;
        $return_data['field_list_items']    = explode("\n", $return_data['field_list_items']);
        $return_data['field_required']      = ($return_data['field_required'] == 'y');
        
        return $return_data;
    }
    
    
    /**
     * Returns the package name.
     *
     * @access  public
     * @return  string
     */
    public function get_package_name()
    {
        return $this->_package_name;
    }
    
    
    /**
     * Returns information about the specified relationship.
     *
     * @access  public
     * @param   int|string      $rel_id     The relationship ID.
     * @return  array
     */
    public function get_relationship_data($rel_id)
    {
        global $DB;
        
        if ( ! $rel_id OR ! is_numeric($rel_id))
        {
            return FALSE;
        }
        
        $db_result = $DB->query("SELECT rel_child_id, rel_data, rel_id, rel_parent_id, rel_type, reverse_rel_data
            FROM exp_relationships
            WHERE rel_id = '{$rel_id}'
            LIMIT 1");
        
        return $db_result->num_rows
            ? $db_result->row
            : FALSE;
    }
    
    
    /**
     * Returns the site ID.
     *
     * @access  public
     * @return  int
     */
    public function get_site_id()
    {
        global $PREFS;
        
        if ( ! $this->_site_id)
        {
            $this->_site_id = (int) $PREFS->ini('site_id');
        }
        
        return $this->_site_id;
    }
    
    
    /**
     * Returns all the entries from the specified weblog, ordered by title.
     * Just returns the entry_id and title at present, as that is all that's
     * needed.
     *
     * @access  public
     * @param   int|string      $weblog_id      The weblog ID.
     * @param   int|string      $site_id        The site ID.
     * @return  array
     */
    public function get_weblog_entries($weblog_id, $site_id)
    {
        global $DB;
        
        // Get out early.
        if ( ! $weblog_id OR ! is_numeric($weblog_id)
            OR ! $site_id OR ! is_numeric($site_id))
        {
            return array();
        }
        
        $db_result = $DB->query("SELECT entry_id, title
            FROM exp_weblog_titles
            WHERE site_id = '{$site_id}' AND weblog_id = '{$weblog_id}'
            ORDER BY title ASC");
        
        return $db_result->num_rows ? $db_result->result : array();
    }
    
    
    /**
     * Retrieves the specified fields for the specified entry ID.
     *
     * @access  public
     * @param   int|string      $entry_id       The weblog entry ID.
     * @param   array           $fields         An array of field IDs.
     * @return  array
     */
    public function get_weblog_entry_field_data($entry_id, Array $fields = array())
    {
        global $DB;
        
        // Get out early.
        if ( ! $entry_id OR ! is_numeric($entry_id) OR ! $fields)
        {
            return FALSE;
        }
        
        // Ensure that all field IDs are in the format `field_id_x`
        for ($counter = 0, $length = count($fields); $counter < $length; $counter++)
        {
            if ( ! strstr($fields[$counter], 'field_id_'))
            {
                $fields[$counter] = 'field_id_' .$fields[$counter];
            }
        }
        
        $sql = "SELECT " .implode(', ', $fields) ." FROM exp_weblog_data
            WHERE entry_id = '{$entry_id}' LIMIT 1";
        
        $db_result = $DB->query($sql);
        
        return $db_result->num_rows ? $db_result->row : FALSE;
    }
    
}


/* End of file      : saef_field_model.php */
/* File location    : system/plugins/saef_field/saef_field_model.php */
