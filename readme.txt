=== Kount Fraud Prevention ===
Contributors: toddfunke
Tags: chargeback, fraud prevention, kount, woocommerce
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Tested up to: 6.6.2
Stable tag: 2.0.1

Kount provides industry-leading fraud protection to reduce chargebacks and manual reviews while increasing approval rates and revenue.

== Description ==

Version 2.x supports Kount K360 Payments and no longer uses PHP Sessions!

Kount's Fraud Protection Platform is the industry choice for advanced protection. Recognized as the leader by analysts such as Forrester, Quadrant, 451 Research, and Frost & Sullivan, Kount is advancing protection throughout the customer Journey. Trusted by over 9,000 top brands, Kount's WooCommerce extension immediately delivers accurate, scalable protection.

Stop chargebacks, reduce manual reviews, and accept more orders while reducing customer friction and false positives. Kount's simple extension delivers the full power of Kount's AI-driven fraud prevention solution. Access the most accurate eCommerce fraud protection to immediately improve your profitability. Kount's automated fraud protection has no negative impacts on the merchant's site and won't add friction to the checkout process, with orders being automatically accepted or declined in less than 250 milliseconds to ensure an optimal customer experience.

Get up and running quickly. Easily install and configure the Kount extension in less than an hour to access automated, real-time order decisioning. Immediately gain built-in, comprehensive fraud prevention, order status notifications, and inventory management for your WooCommerce store. With Kount's AI-driven fraud protection you can:

* Reduce manual reviews up to 83%
* Reduce false positives up to 70%
* Reduce chargebacks up to 99%

### Benefits
* Protect against chargebacks and disputes
* Accept more good orders
* Reduce manual reviews and automate decisions
* Install quickly for immediate protection

== Installation ==
Refer to [How to Integrate Kount Fraud Prevention for WooCommerce](https://developer.kount.com/hc/en-us/articles//4411781370644) for detailed steps on the Kount integration and plugin installation.

1.	Log into WordPress with an admin account. Go to __Plugins__, and then click __Add New__.

2.	Search for Kount Fraud Prevention, and then click __Install Now__.

3.	With the plugin installed successfully, it displays in the menu list of WordPress as __Kount__. Click __Kount__, and then enter the following configuration information:

	-	Account Information
		* Enable Plugin: When enabled, provide risk assessments from Kount
		* Client/Merchant ID: When using Command, input the Kount provided 6-digit ID.  When using K360, this value must contain the Client ID provided by Kount.
		* Test Mode Enable: When enabled, all risk assessments and device data collection will operate against Kount's test environments. NOTE: API Keys and Website IDs should be changed to match the test environment.

	-	Payment Settings
		* Payment Workflow Mode: This can be either Pre Authorization or Post Authorization
		* API Key: The API key created in the API Key Management screen of the Agent Web Console (AWC)
		* Kount 360 API Key: The API key created in the K360 Admin UI
		* ENS Callback URL: URL configured in the AWC for receiving Event Notification System messages (This is a read-only field and is used as the value for the ENS API URL configured in the AWC
		* Website ID: The Website ID set up in the AWC
		* Order Cancellation Message: The message displayed when an order is canceled

	-	Event Logging
		* Select logs level: The minimum level to log
		* Logs delete duration (in days): The length of time logs are retained
		* Download log file: Select the file to download

4.	Save the configuration information.

== Changelog ==
-   2.0.1
	* Resolved issue: Improve Device Data Collection by using additiona hooks to different actions since some sites did not function.
	* Resolved issue: Fixed Device Data Collection on some sites by changing the lifecycle of the Cookie that stores the RIS SESS value.
	* Resolved issue: Restored the Download button to the UI for Kount logs.
-   2.0.0
	* K360 support
	* updated javascript to be compatible with jquery 3.x 
	* discontinued the unused New Account Opening feature
-	1.1.5
	* Removed unused features: Removed support for Kount Control.
	* Resolved issue: Fixed warning related to missing array entry
-	1.1.4
	* Resolved issue: Pre-Auth fraud assessments that return Auth=R (review) set the Order Status to On Hold in more situations.
	* Feature enhancement: When Kount changes an Order Status, the accompanying Order Notes are prefaced with "[Kount]".
-	1.1.3
	* Resolved issue: Fixed saving the Kount plugin configuration after enabling Test Mode.
-	1.1.2
	* Resolved issue: Upon an environment error where the cartID is missing, submit a fraud assessment.  Then, store and reuse the Kount Command SESS value. 
-	1.1.1
	* Resolved issue: Handle Woo Commerce order API failures
	* Retry http requests of Kount services in the case communication fails.
-	1.1.0
	* Support for WooCommerce High-Performance Order Storage (HPOS) has been added.
	* The Kount Transaction ID displayed with a WooCommerce Order is now a hyperlink. This link launches Kount transaction details in the Agent Web Console (AWC)
-	1.0.20
	* Feature enhancement: Pre-Auth requests are processed synchronously.  The reply includes the full set of assessment fields, which are 
	  stored in the order data.
-	1.0.19
	* Resolved issue: Handle error cases where Woo Commerce framework passes error objects and nil values to the Kount Plugin.
-   1.0.18    
	* Resolved issue: Validate the $order parameter passed from WooCommerce filter "woocommerce_thankyou_order_received_text" is not null.
-	1.0.17
	* Feature enhancement: Increased the HTTP client timeout from 5 to 35 seconds when making web requests to Kount.
-	1.0.16
	* Resolved issue: Prior to this update, if you didn't select Save Changes during configuration of Kount and then removed the plugin, 
	* some app related data could have remained in WordPress. Now when you uninstall Kount, the app is successfully removed, including 
	* all related files.
	* Resolved issue: The payment type is now sent without conditional modifications.
-	1.0.15
	* Tested with WordPress 6.2.0
-	1.0.14
	* Feature enhancement: Omniscore displays in the Kount Response and Custom Fields section.
-	1.0.13
	* Feature enhancement: My Account Login page now prevents logins for accounts that are designated as Declined
	* Resolved issue: Replaced the Website ID configuration from Account Creation Settings with configured value from Payment Settings
	* Resolved issue: Removed x-correlation-id header from http request  
	* Resolved issue: Fixed an issue where the configuration value “Test Mode” was being disabled
	* Resolved issue: Fixed an issue where the configuration data from the previously removed Kount plugin was not deleted; now when the plugin is deleted, the configuration data is also deleted
-	1.0.12
	* Feature enhancement: Added 'Test Mode Enable' configuration setting
	* Resolved issue: Allowed more than one pre-authorization Kount Update against a Kount Transaction
	* Resolved issue: Rejected more than one pre-authorization Kount Inquiry against the same cart (with different order numbers)
	* Resolved issue: Restored previous configuration settings upon failure to save updated configurations while on the Kount Admin configuration screen
-	1.0.11
	* Resolved issue: Reasonable defaults are set when receiving an undetermined payment type
	* Tested with WordPress 6.1.0
-	1.0.10
	* Resolved issue: 'White screen' in WooCommerce setup wizard caused by invalid PHP formatting
-	1.0.9
	* Feature enhancement: Added the plugin version to the outgoing request
	* Resolved issue: Creating an account on the checkout page resulted in the order not being assessed for risk
-	1.0.8
	* Resolved issue: Multiple RIS/PUT requests sent when "Thank you" page is refreshed
	* Resolved issue: Total not displaying in Agent Web Console (AWC) for pre-authorization orders
	* Resolved issue: Order messages too large for Kount to process (Solution removed unnecessary data from the message)
-	1.0.7
	* Feature enhancement: Refined logging
	* Resolved issue: Stopped ignoring errors on the configuration page, output error message
	* Resolved issue: Pre-authorization flow sends TRAN="A" instead of TRAN="D"
	* Resolved issue: Fixed Site ID so it persists
	* Resolved issue: Multiple Kount transaction IDs associated with a single order
-	1.0.6
	* Feature enhancement: Changing an order in the Agent Web Console (AWC) from 'Review' or 'Escalate' to 'Approve' changes an 'On Hold' order in WooCommerce to 'Processing'
-	1.0.5
	* Resolved issue: Removed BOM encoding from a file that caused plugin issues on certain hosts
	* Resolved issue: Removed use of deprecated constructors in PHP
-	1.0.3
	* Initial release

== 3rd Party Integrations ==
*	Kount.com
	- This plugin is built as an integration into [Kount's award-winning Identity Trust Platform](https://kount.com/). It includes integrations into two Kount products, [Kount Command](https://kount.com/products/kount-command/) and [Kount Control](https://kount.com/solutions/account-takeover-fraud-protection/).  Reach out to Kount for more information about the platform [here](https://kount.com/about/contact/). You must sign a contract with Kount before you can use this plugin.
	- Read the terms of use for Kount's platform [here](https://kount.com/legal/terms-of-use/)
