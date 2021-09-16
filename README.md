[![Build Status](https://api.travis-ci.com/DiscipleTools/disciple-tools-list-exports.svg?branch=master)](https://travis-ci.com/DiscipleTools/disciple-tools-list-export)

![List Exports](https://raw.githubusercontent.com/DiscipleTools/disciple-tools-list-exports/master/documentation/list-exports-banner.png)
# Disciple Tools - List Exports

Add the ability to export csv, bcc, phone lists, and mapping from your custom contacts list.

## Usage

#### Can Do

 - __This plugin helps you build a custom email address list from a custom contact list.__
 (For example: If you need to announce a training to a small group of leaders. Then you can make the custom list for
 those leaders, then use the export bcc link and the plugin will gather all the emails in the list and make them
 available for quickly creating a new email in your email client, and write your message.)
 - __This plugin helps you build a phone number list from a custom contact list.__ (For example: You want to build a
  group Whatsapp message. You could build the custom list of key people through the filter creator on the contact lists
  page of Disciple Tools, click the "phone list" link, and copy the list of the phone numbers and paste them into the
  Whatsapp new message field.)
  - __This plugin helps you build a simple csv formatted list from a custom contact list.__ (For example: If you need to
  do a simple transfer/import of contacts to Mailchimp. You could build your list with the filter tool, and then click
  the "csv list" link to generate the list. Then copy the generated list and past it into the import tool for Mailchimp.)
  - __This plugin helps you generate a quick map from a custom contact list.__

#### Can't Do

- __Does not facilitate integrations to other systems.__ These exports attempt to quickly collect and format contact
information in a way that can be copied and taken to other applications.

####

## Installing

Install as a standard plugin in the WP Admin area of the Disciple Tools system. Requires the role of Administrator.

## Requirements

- __Map feature requires__ the installation of the free Mapbox key in the Disciple Tools mapping section.
- Disciple Tools System


## Contribution

Contributions welcome. You can report issues and bugs in the
[Issues](https://github.com/DiscipleTools/disciple-tools-list-exports/issues) section of the repo. You can present ideas
in the [Discussions](https://github.com/DiscipleTools/disciple-tools-list-exports/discussions) section of the repo. And
code contributions are welcome using the [Pull Request](https://github.com/DiscipleTools/disciple-tools-list-exports/pulls)
system for git. For a more details on contribution see the [contribution guidelines](https://github.com/DiscipleTools/disciple-tools-list-exports/blob/master/CONTRIBUTING.md).


## Screenshots

#### Example of the Contact List Tile

This tile is added to the left side of the Contacts list menu. Once you have created your list by using default or custom filters, you can select the type of export.

![tile](https://raw.githubusercontent.com/DiscipleTools/disciple-tools-list-exports/master/documentation/list-exports.png)

---

#### Example of the BCC email list page

The purpose of the BCC email tool is to create a list of contact you want to communicate with and to create a BCC email that hide the email addresses from the recipients. This allows you to use your default email client to distribute a bulk communication and it will come from your email address and replies will come to your email address.

Because BCC is not recommended beyond 50 addresses, the export groups the addresses into groups of 50. It also lists what contacts do not have addresses and are not included in the export.


![bcc](https://raw.githubusercontent.com/DiscipleTools/disciple-tools-list-exports/master/documentation/bcc.png)

In the example above, when you click the button "Open Group 1", this will load the addresses into your default email client (either your online email or local software. Whatever your computer is set to open when clicking an email link.)

If you click the link to show addresses, you can copy these and take them into your client manually.

---

#### Example of the Phone List

You can highlight and copy the list of phone numbers and paste them into WhatsApp, Messages, Signal, or your texting app. This allows for a fast way to build a group conversation. At the bottom of the list (or if you click the link "Full List" to collapse it), the number of contacts without a phone number is listed.

![phone](https://raw.githubusercontent.com/DiscipleTools/disciple-tools-list-exports/master/documentation/phone-collapsed.png)

![phone](https://raw.githubusercontent.com/DiscipleTools/disciple-tools-list-exports/master/documentation/phone.png)

---

#### Example of the CSV export

This is a simple csv export which is designed for copy and pasting this list into other applications like Mailchimp. The export is formatted in proper csv format and could be pasted into Google Docs or Excel as well.

![csv](https://raw.githubusercontent.com/DiscipleTools/disciple-tools-list-exports/master/documentation/csv.png)

---

#### Example of the Map (Mapbox Key Required)

This map visualization of your contact list is extremely powerful for understanding where your custom list is geographically. The totals at the top of the map indicate how many contacts have been mapped and how many do not have any known geolocated address. The Mapbox key is required to enable the points map.

If the number of contacts is over 100, then the points on the map must be hovered over in order to see the details of the contact. If the list is less than 100, then the contact details box is enabled for all the contacts. If you click the map, you can close all the boxes. If you hover or click the contact point you can open the details box.


![map](https://raw.githubusercontent.com/DiscipleTools/disciple-tools-list-exports/master/documentation/map.png)
