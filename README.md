# magerun-addons

Purpose of this project is to check for different flaws that can occur due to EAV and provide cleanup functions.

What do we check for:
* `eav:clean:product-attribute-set-values` Check for abandoned attribute values in eav tables. The attribute was removed from an attribute set the values still exist. Use --dry-run to check result without modifying data. (done)
* `eav:clean:scope-values` Check for wrong scopes. Values in Scopes which shouldn't be present. For example values on Storeview level but attribute scope is global. Use --dry-run to check result without modifying data. (done)
* Check if admin value and storeview value are the same, so use default doesn't work anymore. Delete the storeview values. (done)
* Check if a sourcemodel is used but not allowed for attributes like text. (done)
* `eav:check:models` Check if the assigned sourcemodel, backendmodel and frontendmodel still exist. (done)
* Check if the attributetype was changed for example from text to select and delete wrong values. (TODO)
* `eav:clean:removed-store-view-values` Remove attribute values for storeviews that don't exist anymore. Use --dry-run to check result without modifying data. (done)
* `eav:check:media` List unused product images

### Ideas

* check for unused images
* remove unused attributes

### Contributors
Benno Lippert
Damian Luszczymak
Joke  Puts
Peter Jaap
Ralf Siepker
