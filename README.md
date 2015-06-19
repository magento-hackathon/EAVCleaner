# magerun-addons

Purpose of this project is to check for different flaws that can occur due to EAV and provide cleanup functions.

What do we check for:
* `eav:clean:product-attribute-set-values` Check for abandoned attribute values in eav tables. The attribute was removed from an attribute set the values still exist. Use --dry-run to check result without modifying data. (done)
* `eav:clean:scope-values` Check for wrong scopes. Values in Scopes which shouldn't be present. For example values on Storeview level but attribute scope is global. Use --dry-run to check result without modifying data. (done)
* `eav:restore-use-default-value` Check if product attribute admin value and storeview value are the same, so "use default" doesn't work anymore. Delete the storeview values. Use --dry-run to check result without modifying data. (done)
* Check if a sourcemodel is used but not allowed for attributes like text. (done)
* `eav:check:models` Check if the assigned sourcemodel, backendmodel and frontendmodel still exist. (done)
* Check if the attributetype was changed for example from text to select and delete wrong values. (TODO)
* `eav:clean:removed-store-view-values` Remove attribute values for storeviews that don't exist anymore. Use --dry-run to check result without modifying data. (done)
* `eav:check:media` List unused product images
* `eav:clean:entity-type-values` Remove attribute values with wrong entity_type_id. For example the table catalog_product_entity_int should only contain entries with entity_type_id == 10. Use --dry-run to check result without modifying data.
* `eav:clean:attributes-and-values-without-parent` Remove catalog_eav_attribute and attribute values which are missing parent entry in eav_attribute. This can happen after importing data with foreign key check switched off. Use --dry-run to check result without modifying data.

### Ideas

* check for unused images
* remove unused attributes

### Installation
see https://github.com/netz98/n98-magerun/wiki/Modules#where-can-modules-be-placed

### Contributors
Benno Lippert
Damian Luszczymak
Joke  Puts
Peter Jaap
Ralf Siepker
