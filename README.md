OWDatabaseCheckup
====================

Extension : OWDatabaseCheckup v0.1  
Requires : eZ Publish 4.x and 5.x  
Author : Open Wide <http://www.openwide.fr>


What is OWDatabaseCheckup ?
------------------------------

OWDatabaseCheckup provides php cli scripts for common database maintenance.



fixcontentobjectattributes.php
----------------------------------

Removing a class attribute should also remove all corresponding object attributes. If it doesn't for any reason, object attributes remain orphans in ezcontentobject_attribute table and may cause fatal errors.

This script gets all orphans object attributes and remove them from ezcontentobject_attribute table.

    php bin/php/fixcontentobjectattributes.php [--dry-run] [--remove-objectattributes]

* `--dry-run` option : displays the missing class attributes IDs and the total number of orphan content attributes
* `--remove-objectattributes` option : removes all orphan content attributes



fixobjectswithoutmainnode.php
----------------------------------

Content objects are always published with at least one main node. Sometimes, using the API to create content can lead to published objects with no nodes.

This scripts gets all published content objects which do not have any nodes avoiding in trash objects and drafts. Then you can add a new main node or delete them.

    php bin/php/fixobjectswithoutmainnode.php [--dry-run] [--update-objects] [--target-node=VALUE] [--remove-objects]

* `--dry-run` option : displays the IDs and the total number of published content objects which do not have any nodes
* `--update-objects` option : adds a new main node to these content objects
* `--target-node=VALUE` option : use it with `--update-objects` option to specifiy the parent node ID of the new main nodes
* `--remove-objects` option : removes content objects which do not have any nodes



CAUTION
------------------------------

These scripts directly modifies your eZ Publish database records. **You shall back it up before running them !**



What's coming next ?
------------------------------

* Other database inconsistencies may occure. New scripts should be added in the future.
* Back-office interface to run checkups