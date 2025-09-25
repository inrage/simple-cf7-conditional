=== Simple Conditional Fields for Contact Form 7 ===
Contributors: inrage
Author: inrage
Website: https://www.inrage.fr
Tags: contact form 7, forms, conditional fields, conditional logic, visual interface, groups
Requires at least: 5.0
Tested up to: 6.8.2
Requires PHP: 7.4
Requires Plugins: contact-form-7
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple and intuitive plugin to add conditional fields to Contact Form 7 forms with visual interface.

== Description ==

**Simple Conditional Fields for Contact Form 7** adds powerful conditional logic to [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) with an intuitive visual interface.

Unlike other conditional field plugins, this one focuses on **simplicity and user experience** with:

* **Visual drag-and-drop interface** - No complex syntax to learn
* **Real-time field detection** - Automatically scans your form as you type
* **Group-based approach** - Organize related fields together
* **Modern interface** - Clean, intuitive admin panel
* **Multilingual support** - Available in English and French
* **Secure and optimized** - Built with WordPress best practices

= Key Features =

✅ **Visual Rule Builder** - Create conditional rules with an intuitive interface
✅ **Real-time Form Scanning** - Automatically detects fields and groups as you edit
✅ **Group Management** - Enhanced group tag generator with closing tags
✅ **Multiple Operators** - Equals, contains, is empty, greater than, and more
✅ **Smart Performance** - Scripts load only when needed
✅ **Translation Ready** - Fully translatable with French included
✅ **Security First** - Nonce verification, data sanitization, capability checks

= How It Works =

1. **Create Groups**: Use the enhanced `[group]` tag generator to wrap related fields
2. **Set Conditions**: Use the visual interface to create rules like "Show Group A when Field B equals 'Yes'"
3. **Live Preview**: Rules are applied instantly on the frontend

= Example Usage =

**Step 1**: Wrap fields in groups
```
[group contact-method]
  [tel phone-number "Phone Number"]
  [email email-address "Email Address"]
[/group]

[group business-info]
  [text company "Company Name"]
  [textarea business-description "Business Description"]
[/group]
```

**Step 2**: Create conditional rules in the visual interface:
- Show "contact-method" when "contact-type" equals "business"
- Show "business-info" when "contact-type" equals "business"

**Step 3**: Save and test your form!

= Compatibility =

* **WordPress**: 5.0+ (tested up to 6.8.2)
* **PHP**: 7.4+
* **Contact Form 7**: Required dependency
* **Browsers**: All modern browsers

Simple Conditional Fields for Contact Form 7 is an independent plugin. This plugin is not affiliated with or endorsed by the developers of Contact Form 7.

== Installation ==

= Automatic Installation =

1. Go to your WordPress admin area and navigate to **Plugins > Add New**
2. Search for "Simple Conditional Fields for Contact Form 7"
3. Click **Install Now** and then **Activate**
4. Contact Form 7 will be installed automatically if not present

= Manual Installation =

1. Download the plugin zip file
2. Upload to `/wp-content/plugins/` directory
3. Extract the zip file
4. Activate the plugin through the **Plugins** menu in WordPress
5. Make sure Contact Form 7 is installed and activated

= Getting Started =

1. Edit any Contact Form 7 form
2. Use the **group** tag generator to create field groups
3. Go to the **Simple Conditional Fields** tab
4. Create your conditional rules using the visual interface
5. Save and test your form

== Frequently Asked Questions ==

= Do I need to know how to code? =

No! The plugin features a visual interface where you can create conditional rules by selecting options from dropdowns. No coding knowledge required.

= What's the difference between this and other conditional field plugins? =

This plugin focuses on **user experience and simplicity**:
- Visual drag-and-drop interface instead of text-based configuration
- Real-time field detection as you type
- Modern, intuitive admin panel
- Enhanced group tag generator
- Built-in multilingual support

= Can I nest groups inside other groups? =

Currently, the plugin works with top-level groups. Nested groups may be added in future versions based on user feedback.

= What conditional operators are available? =

The plugin supports these operators:
- **Equals** - Field value matches exactly
- **Not Equals** - Field value doesn't match
- **Contains** - Field value contains text
- **Does Not Contain** - Field value doesn't contain text
- **Is Empty** - Field has no value
- **Is Not Empty** - Field has a value
- **Greater Than** - For numeric comparisons
- **Less Than** - For numeric comparisons

= Is the plugin translation-ready? =

Yes! The plugin is fully translatable and includes French translations. You can contribute translations in your language.

= Does it work with required fields? =

Yes! Hidden fields are automatically disabled and won't trigger validation errors when their group is hidden.

= Can I use it with other Contact Form 7 add-ons? =

The plugin is designed to work alongside other CF7 extensions. However, we recommend testing with your specific setup.

= Is there a Pro version? =

Currently, this is a free plugin with all features included. Future premium features may be considered based on user feedback.

== Screenshots ==

1. **Visual Rule Builder** - Create conditional rules with an intuitive drag-and-drop interface
2. **Real-time Field Detection** - Automatically scans your form as you edit
3. **Enhanced Group Generator** - Improved tag generator with closing tags
4. **Modern Admin Interface** - Clean, sidebar-based layout
5. **Multiple Operators** - Various condition types for complex logic
6. **Live Frontend** - Rules applied instantly without page refresh

== Changelog ==

= 1.0.0 - 2025-01-25 =

**Initial Release**

**Features:**
* Visual conditional rule builder with drag-and-drop interface
* Real-time form field detection and scanning
* Enhanced group tag generator with automatic closing tags
* Support for multiple conditional operators (equals, contains, empty, etc.)
* Modern sidebar-based admin interface
* Multilingual support with French translation included
* Smart performance optimization (scripts load only when needed)
* Complete security implementation (nonce verification, data sanitization)
* Integration with Contact Form 7's native workflow

**Technical:**
* Built with WordPress coding standards
* Secure data handling and validation
* Optimized JavaScript with error handling
* Responsive CSS design
* Translation-ready with POT/PO/MO files
* Proper plugin dependency declaration

**Developer Features:**
* Clean, documented codebase
* Separation of concerns (admin, frontend, conditions)
* Extensible architecture
* WordPress hooks and filters integration

== Upgrade Notice ==

= 1.0.0 =
Initial release of Simple Conditional Fields for Contact Form 7. A fresh approach to conditional logic with visual interface and enhanced user experience.

== Support ==

For support, feature requests, or bug reports:

* **Documentation**: [Plugin Documentation](https://www.inrage.fr/plugins/simple-cf7-conditional)
* **Support Forum**: [WordPress.org Support](https://wordpress.org/support/plugin/simple-cf7-conditional)
* **Contact**: contact@inrage.fr

== Contributing ==

We welcome contributions! If you'd like to contribute:

* **Translations**: Help translate the plugin into your language
* **Bug Reports**: Report issues on the support forum
* **Feature Requests**: Suggest new features
* **Code Contributions**: Submit pull requests for improvements

== Privacy ==

This plugin does not collect, store, or transmit any personal data. All form processing is handled by Contact Form 7 according to their privacy policy.