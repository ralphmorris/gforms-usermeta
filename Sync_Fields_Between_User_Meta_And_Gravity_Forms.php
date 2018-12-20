<?php

/**
 *
 *	Keeps field synced between user_meta and gravity forms fields so that the user doesn't have to 
 *	type in the same meta fields again and again.
 * 
 *	Usage: 
 *	
 *	1. In Gravity Forms, add a class name of 'dynamic' to the Custom CSS Class field. 
 *	2. Go to the Advanced Field settings and check the box 'Allow this field to be dynamically populated'.
 *	3. Add a unique user_meta key to the Parameter Name field that shows up.
 *	4. Save!
 *
 *	Requires User::class also but can easily be swapped out for WP Core functions.
 * 
 */

class Sync_Fields_Between_User_Meta_And_Gravity_Forms
{
	/**
	 * The class name the user needs to attach to a field to make it dynamic
	 * 
	 * @var string
	 */
	private $className = 'dynamic';

	/**
	 * TD_Models/User.php
	 * 
	 * @var Object
	 */
	protected $user;

	public function __construct()
	{
		add_action( 'gform_after_submission', [$this, 'handle'], 10, 2 );

		add_filter( 'gform_field_value', [$this, 'populateField'], 10, 3 );
	}

	/**
	 * Handle the form submission
	 * 
	 * @param  [array] $entry
	 * @param  [array] $form 
	 * @return void
	 */
	public function handle( $entry, $form ) 
	{
		$this->user = new User;

	    foreach ( $form['fields'] as $field ) 
	    {
	    	if ($this->isDynamicField($field)) 
	    	{
	    		$this->storeMetaForField($field, $entry);
	    	}
	    }
	}

	/**
	 * Is the given field dynamic?
	 * 
	 * @param  object $field
	 * @return boolean
	 */
	protected function isDynamicField($field)
	{
		return in_array($this->className, explode(' ', $field->cssClass));
	}

	/**
	 * A gforms field may have multiple actual inputs. If so get all the inputs and store the meta.
	 * If not take the given field and store it's single meta.
	 * 
	 * @param  object $field
	 * @param  array $entry
	 * @return void       
	 */
	public function storeMetaForField($field, $entry)
	{
        if ( isset($field['inputs']) ) 
        {
            foreach ( $field['inputs'] as $input ) 
            {
                $value = rgar( $entry, (string) $input['id'] );

                $this->updateMeta($input['name'], $value);
            }
        } else 
        {
            $value = rgar( $entry, (string) $field->id );

            $this->updateMeta($field->inputName, $value);
        }
	}

	/**
	 * Update the users meta
	 *
	 * @uses  TD_Models/User.php
	 * 
	 * @param  [string] $key   [meta_key]
	 * @param  [string] $value [meta_value]
	 * @return void
	 */
	public function updateMeta($key, $value)
	{
		$this->user->save($key, $value);
	}

	/**
	 * Dynamically populate the dynamic field
	 * 
	 * @param  string $value
	 * @param  object $field
	 * @param  string $name [the dynamically populate gforms parameter]
	 * @return string|false
	 */
	public function populateField( $value, $field, $name ) 
	{
		if ($this->isDynamicField($field)) 
		{
		    return get_user_meta(get_current_user_id(), $name, true);
		}

		return false;
	}
}

new Sync_Fields_Between_User_Meta_And_Gravity_Forms;
