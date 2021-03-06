<?php

    namespace KP\Post;
    use KP\Post;
    use KP\KP_Entity;

    class KP_Metabox extends KP_Entity {
        private $fields;
        
        public $section;
        public $priority;
        
        /**
         * Slug should not contain any special characters, spaces, capitals etc.
         */
        public function __construct( $label ) {
            
            //save the label of the post type and create a machine name to reference it by
            parent::__construct( $label );
            
            //an array of custom field or text editor objects
            $this->fields = array();
            
            //add hook for custom post types
            add_action( 'save_post', array( $this, 'update_post_meta_data' ) );
            
            //don't update the post with the data stored in $_POST if transitioning from scheduled to published
            add_action( 'future_to_publish', array( $this, 'remove_post_update' ) );
        }
        
        /**
         * Remove the update hook when transitioning a post between different post status',
         *  preventing loss of already saved metadata.
         * 
         * @todo Elaborate on why this happens in comment description.
         * 
         * @return void
         */
        public function remove_post_update() {
            //remove the action to save the metadata using the $_POST data
            remove_action( 'save_post', array( $this, 'update_post_meta_data' ) );
        }
        
        /**
         * Update the database with the values of the custom fields or editor entered by the user.
         * 
         * The reason there is a dedicated function to run $this–>update_fields() is for implementing
         * additional TinyMCE editors in metaboxes. The contents of this proposed function
         * will differ from $this->update_fields().
         * 
         * @params int $post_id The post ID. Automatically passed by Wordpress when invoked.
         * 
         * @return void
         */
        public function update_post_meta_data( $post_id ) {
            $this->update_fields( $post_id );
            $this->update_editors( $post_id );
        }
        
        /**
         * Save the values entered in the metabox fields into the database.
         * 
         * Fields need to be registered to a metabox using the add_field() function
         * 
         * @see KP_Metabox::add_field()
         * @see KP_Custom_Field
         */
        public function update_fields( $post_id ) {
            
            /**
             * Each field must be assigned to the metabox using $this->add_field().
             */
            foreach( $this->fields as $field ) {
                
                // If the field textarea has been populated, then we sanitize the information.
                if ( isset( $_POST[$field->machine_name] ) ) {
                    
                    //checkboxes only get added to the $_POST array if they have been checked
                    if ( 'checkbox' === $field->input_type ) {
                        
                        $field_content = 1;
                        
                    } else {
                        
                        // We'll remove all white space, HTML tags, and encode the information to be saved
                        $field_content = trim( $_POST[$field->machine_name] );
                        $field_content = esc_textarea( strip_tags( $field_content ) );
                        
                    } //end ifelse statement
                    
                    //save custom field to database
                    update_post_meta( $post_id, $field->machine_name, $field_content );
 
                } else {
                     
                    //if no content exists in the field, remove any existing value from the database
                    if ( '' !== get_post_meta( $post_id, $field->machine_name, true ) ) {
                        
                        //remove from database
                        delete_post_meta( $post_id, $field->machine_name );
                        
                    }//end if statement
                     
                }//end ifelse
                
            }//end foreach
        }
        
        /**
         * Save the content entered in the TinyMCE editors into the database.
         * 
         * Fields need to be registered to a metabox using the add_field() function
         * 
         * @see KP_Metabox::add_editor()
         * @see KP_Text_Editor
         */
        public function update_editors( $post_id ) {
        }
        
        /**
         * Add a new field to the metabox.
         * 
         * Input types are the types used in HTML forms <input></input>
         *  If no input_type is defined, the default input is text
         * 
         * @see KP_Metabox::display_field()
         * @see KP_Entity::create_machine_name()
         * 
         * @param string $field_name The label to display for the field. Also used to create the machine_name.
         * @param string $input_type The type of <input> element.
         * @param string[] $input_values Any options required for the <input> element, such as radio button options.
         * 
         * @return Csnqc_Custom_Field $field The field object created by the function.
         */
        public function add_field( $field_name, $required = false, $input_type = 'text', $input_values = array() ) {
            
            //Create a new custom field object
            $field = new KP_Custom_Field( $field_name, $this, strval( $input_type ), $input_values );
            
            //Set as required (for form validation)
            $field->required = $required;
            
            //add the field to the metabox's fields array
            $this->fields[] = $field;
            
            return $field;
        }
        
        /**
         * Add a new TinyMCE text editor to the metabox.
         * 
         * @see KP_Metabox::display_editor()
         */        
        public function add_editor() {
        }
        
        private function display_field() {
        }
        
        private function display_editor() {
        }
        
        
        
        
        
    }//end metabox class