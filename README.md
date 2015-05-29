# magerun-addons

Purpose of this project is to check for different flaws that can occure due to EAV and provide cleanup functions.

What do we check for:
* Check for abandoned attribute values in eav tables. The attribute was deleted values still exist.
* Check for wrong scopes. Values in Scopes which shouldn't be present. For example values on Storeview level but attribut scope is global.
* Check if admin value and storeview value are the same, so use default doesn't work anymore. Delete the storeview values.
* Check if a sourcemodel is used but not allowed for attributes like text.
* Check if the assigned sourcemodel, backendmodel still exist.
* Check if the attributetype was changed for example from text to select and delete wrong values.
* Check if there are values for storeviews that don't exist anymore (shouldn't happen)