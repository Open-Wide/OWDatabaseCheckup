#!/usr/bin/env php
<?php

require_once 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance(
    array(
        'description' => "Gets all object attributes missing their corresponding class attribute and delete them",
        'use-session' => false,
        'use-modules' => false,
        'use-extensions' => true
    )
);

$script->startup();
$options = $script->getOptions( "[remove-objectattributes][dry-run]", 
                                "", 
                                array( "remove-objectattributes" => "Remove object attributes which do not match a class attribute anymore" , 
                                       "dry-run"                 => "Runs the checkup but do not do anything") );
$script->initialize();

$db = eZDB::instance();
// Get contentclassattribute_ids of all object attributes which don't have a corresponding class_attribute anymore
$rows = $db->arrayQuery( "select contentclassattribute_id from ezcontentobject_attribute where contentclassattribute_id not in (select id from ezcontentclass_attribute);" );
$classAttributeIDs = $db->arrayQuery( "select distinct(contentclassattribute_id) from ezcontentobject_attribute where contentclassattribute_id not in (select id from ezcontentclass_attribute);" );


// DRY RUN
if ( $options['dry-run'] ) {
    $cli->output( "Running script in dry-run mode." );

    if (count($rows)) {
        $cli->output( "Found " . count($rows) . " object attributes missing their class attribute." );
        foreach( $classAttributeIDs as $classAttributeID ) {
            $cli->output( "Class attribute " . $classAttributeID['contentclassattribute_id'] . " is missing." );
        }
        $script->shutdown( 0 , "You should delete these object attributes. Run this script with \"--remove-objectattributes\" option." );
    }
    else {
        $cli->output( "No object attributes missing their class attribute." );
        $script->shutdown( 0 , "Nothing to do !" );
    }
}


// REMOVE OBJECT ATTRIBUTES
if ($options['remove-objectattributes']) {
    if (count($rows)) {
        $cli->output( "Found " . count($rows) . " object attributes missing their class attribute." );

        foreach( $classAttributeIDs as $classAttributeID ) {
            $db->begin();
            $result = $db->query( "delete from ezcontentobject_attribute where contentclassattribute_id = " . $classAttributeID['contentclassattribute_id'] . ";" );
            $db->commit();
            
            if ($result) {
                $cli->output( "Object attributes from contentclassattribute_id " . $classAttributeID['contentclassattribute_id'] . " have been removed." );
            }
            else {
                $cli->output( "Unable to remove object attributes from contentclassattribute_id " . $classAttributeID['contentclassattribute_id'] . "." );
            }
        }

        $script->shutdown( 0 , "Finished removing object attributes." );
    }
    else {
        $cli->output( "No object attributes missing their class attribute." );
        $script->shutdown( 0 , "Nothing to do !" );
    }
}


$script->shutdown( 0 , "No options given. Try to run with \"--dry-run\" option." );