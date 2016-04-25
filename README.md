# EAV Cleaner Console Command

Purpose of this project is to check for different flaws that can occur due to EAV and provide cleanup functions.

Use --dry-run to check result without modifying data.

# Magento 2

You've found the Magento 2 version. Yay for you!

# Commands

* `eav:config:restore-use-default-value` Check if config admin value and storeview value are the same, so "use default" doesn't work anymore. Delete the storeview values.
* `eav:attributes:restore-use-default-value` Check if product attribute admin value and storeview value are the same, so "use default" doesn't work anymore. Delete the storeview values.
* `eav:attributes:remove-unused` Remove attributes with no values set in products and attributes that are not present in any attribute sets.
* `eav:media:remove-unused` Remove unused product images.

### Ideas

See [issues labeled enhancement](https://github.com/magento-hackathon/EAVCleaner/issues?q=is%3Aissue+is%3Aopen+label%3Aenhancement)

### Installation

Use composer or copy the app/code/EAVCleaner folder to your installation.

### Usage

Run `bin/magento` in the Magento 2 root and look for the `eav:` commands.

### Contributors
- Nikita Zhavoronkova
- Anastasiia Sukhorukova
- Peter Jaap Blaakmeer

### Special thanks to
- Benno Lippert
- Damian Luszczymak
- Joke Puts
- Ralf Siepker