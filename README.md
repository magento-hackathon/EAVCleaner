# magerun-addons

Purpose of this project is to check for different flaws that can occure due to EAV and provide cleanup functions.

What do we check for:
* Check for abandoned attribute values in eav tables. The attribute was removed from an attribute set the values still exist. (done)
* Check for wrong scopes. Values in Scopes which shouldn't be present. For example values on Storeview level but attribut scope is global. (done)
* Check if admin value and storeview value are the same, so use default doesn't work anymore. Delete the storeview values. (done)
* Check if a sourcemodel is used but not allowed for attributes like text. (done)
* Check if the assigned sourcemodel, backendmodel still exist. (done)
* Check if the attributetype was changed for example from text to select and delete wrong values. (TODO)
* Check if there are values for storeviews that don't exist anymore (shouldn't happen) (done)

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
