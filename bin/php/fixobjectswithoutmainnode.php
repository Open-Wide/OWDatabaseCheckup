#!/usr/bin/env php
<?php

require_once 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance(
    array(
        'description' => "Add a main node or delete objects without any nodes",
        'use-session' => false,
        'use-modules' => false,
        'use-extensions' => true
    )
);

$script->startup();
$options = $script->getOptions( "[update-objects][target-node:][remove-objects][dry-run]", 
                                "", 
                                array( "update-objects" => "Add a new main node to the objects",
                                       "target-node"    => "Where do you want to create the objects'new main nodes ? (RootNode INI parameter will be used if not set)", 
                                       "remove-objects" => "Avoid adding new nodes and removes the objects",
                                       "dry-run"        => "Runs the checkup but do not do anything"));
$script->initialize();

$db = eZDB::instance();
// Get IDs of all objects which don't have any nodes and are neither in trash nor drafts (status 2 = in trash , status 0 = draft)
$rows = $db->arrayQuery( "select id, status from ezcontentobject where id not in (select contentobject_id from ezcontentobject_tree) and status = 1;" );


// DRY RUN
if ( $options['dry-run'] ) {
    $cli->output( "Running script in dry-run mode." );

    if (count($rows)) {
        $cli->output( "Running script in dry-run mode." );
        $cli->output( "IDs of objects without any nodes" );
        foreach($rows as $object) {
            $cli->output( "Not any nodes for object " . $object['id'] . " !");
        }
        $cli->output( "Total : " . count($rows) );
        $script->shutdown( 0 , "You should fix these objects without any nodes. Run this script with \"--update-objects\" and \"--target-node\" options or just \"--remove-objects\" option.");
    }
    else {
        $cli->output( "No published objects without any nodes found." );
        $script->shutdown( 0 , "Nothing to do !" );
    }
}

// REMOVE OBJECTS
if ($options['remove-objects']) {
    foreach( $rows as $object ) {
        $db->begin();
        $removed = eZContentObjectOperations::remove( $object['id'] );
        $db->commit();

        if($removed) {
            $cli->output( "Object " . $object['id'] . " has been removed." );
        }
        else {
            $cli->output( "Unable to remove object " . $object['id'] );
        }
    }

    $script->shutdown( 0 , "Finished removing the objects");
}


// ADD A MAIN NODE
if ($options['update-objects']) {
    $INI = eZINI::instance('content.ini');
    $targetNodeID = $options['target-node'] ? $options['target-node'] : $INI->variable('NodeSettings', 'RootNode');
    $cli->output( "New nodes will be created under node " . $targetNodeID );

    foreach( $rows as $object ) {
        $db->begin();
        
        $contentObject = eZContentObject::fetch( $object['id'] );

        if ($contentObject instanceof eZContentObject) {
            $cli->output( "Adding a main node to object " . $contentObject->attribute('id') );
            
            // Add a new node to the contentObject
            $newNode = $contentObject->addLocation( $targetNodeID );

            if ($newNode) {
                // Set the new node as main node
                $updateMainNode = eZContentOperationCollection::updateMainAssignment( $newNode, 
                                                                                      $contentObject->attribute('id'), 
                                                                                      $targetNodeID );
                $cli->output( "Added main node " . $newNode );
            }
            else {
                $cli->output( "Unable to add node to object " . $contentObject->attribute('id') );
            }
        }
        else {
            $cli->output( "Unable to fetch object " . $object['id'] );
        }
        
        $db->commit();
    }

    $script->shutdown( 0 , "Finished updating the objects");
}


$script->shutdown( 0 , "No options given. Try to run with \"--dry-run\" option." );

?>