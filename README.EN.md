[**Wersja polska**][ext8]

# PayU module for Magento 2 version 2.4
## BREAKING CHANGES
**Due to changes described in [CHANGELOG][ext10] after updating plugin from version 1.X to 2.X, you must reconfigure plugin.**

**If you have any questions or if you want to report an error, please contact our support at the address: tech@payu.pl.**

* If you are using Magento version 1.x, please use a [plugin for version 1.x][ext0]
* If you are using Magento version >2.0.6, 2.1, 2.2, please use a [plugin for version >2.0.6, 2.1, 2.2][ext7]
* If you are using Magento version 2.3, please use a [plugin for version 2.3][ext9]

## Table of contents

1. [Properties](#properties)
1. [Requirements](#requirements)
1. [Installation](#installation)
1. [Configuration](#configuration)
    * [Parameters](#parameters)
1. [Information about properties](#information-about-properties)
    * [Order of payment methods](#order-of-payment-methods)
    * [Repeat payment](#repeat-payment)
    * [Saving card](#saving-cards)

## Properties
PayU payment module adds a PayU payment option to Magento 2. The module works together with Magento 2 version 2.4

The following operations are possible:
* Creation of payment in the PayU system
* Automatic receipt of notifications and change of order status
* Receipt or rejection of payment (in case of switched off automatic receipt)
* Display of payment method and selection of the method on the order summary page
* Payment by card directly on the order summary page
* Remembering of cards and payment with the remembered card
* Repeat payment
* Creation of online refund (full or partial)
* Promoting credit payments using [credit widget](#credit-widget)  on different subpages of the store (e.g. on the product page, in the cart)


The module adds these payment methods:
  * **PayU payment** - selection of payment method and redirection a bank or card form
  * **Card payment** - entry of the card number directly on the store's website and payment by card
  * **PayU Installments** - installment payments with a redirect to the PayU installment form.
  * **PayU Klarna** - deferred Klarna payments with a redirect to the Klarna form in PayU.
  * **PayU PayPo** - deferred PayPo payments with a redirect to the PayPo form in PayU.
  * **PayU PragmaPay** - deferred PragmaPay payments with a redirect to the PragmaPay form in PayU.
  * **PayU Twisto** - deferred Twisto payments with a redirect to the Twisto form in PayU.
  * **PayU Twisto Pay in 3** - deferred Twisto Pay in 3 payments with a redirect to the Twisto Pay in 3 form in PayU

![methods][img0]

## Requirements

**Important:** The module works only with the REST API (Checkout) POS, if you don't have an account in the PayU system yet, [**register yourself in the production system**][ext1] or [**in the sandbox system**][ext5]

* PHP compliant with the requirements of the installed version of Magento 2
* PHP extension: [cURL][ext2] and [hash][ext3].

## Installation

#### Using Composer
`composer require payu/magento24-payment-gateway`

#### By copying files to a server
1. Download the latest version of the module from the [GitHub repository][ext4]
1. Unzip the downloaded file
1. Connect to the ftp server and copy the unzipped files to the folder `app/code/PayU/PaymentGateway` of your Magento 2 store. If there is no such folder, create it.

After installation using Composer or copying files from the console's level, run:
   * php bin/magento module:enable PayU_PaymentGateway
   * php bin/magento setup:upgrade
   * php bin/magento setup:di:compile
   * php bin/magento setup:static-content:deploy

## Configuration

1. Go to the administration page of your Magento 2 store [http://adres-sklepu/admin_xxx].
1. Go to **Stores** > **Configuration**.
1. On the **Configuration** page in the menu on the left-hand side, in the section **Sales** choose **Payment Methods**.
1. On the list of available payment methods choose one of the **PayU** methods to configure the plugin's parameters.
1. After changing the parameters click `Save config`.

### API Parameters

| Parameter              | Description                                                                                                                              |
|------------------------|-------------------------------------------------------------------------------------------------------------------------------------------|
| Test mode (Sandbox)    | `Yes` - transactions are processed by the PayU Sandbox system. <br/> `No` - transactions are processed by the PayU production system.   |


#### Point of sale (POS) parameters

| Parameter | Descripction |
|---------|-----------|
| POS IdD | POS ID from PayU system |
| Second MD5 key | Second MD5 key from PayU system |
| OAuth - client_id | client_id for OAuth protocol from PayU system |
| OAuth - client_secret | client_secret for OAuth from PayU system |

#### POS parameters - Test mode (Sandbox)
Available when the parameter `Test Mode (Sandbox)` is set for `Yes`.

| Parameter | Description |
|---------|-----------|
| POS ID | POS ID from PayU system |
| Second MD5 key | Second MD5 key from PayU system |
| OAuth - client_id | client_id for OAuth protocol from PayU system |
| OAuth - client_secret | client_secret for OAuth from PayU system |

### "PayU Credit widget" plugin parameters

| Parameter                                      | Description                                                                                                                                                                                                                                   |
|------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Enable widget for product view                 | Value `Yes`\|`No`. Displays the widget on product pages.                                                                                                                                                                                      |
| Enable widget for catalog view                 | Value `Yes`\|`No`. Displays the widget on pages with product lists (e.g., categories).                                                                                                                                                        |
| Enable widget for catalog widgets eg. hot, new | Value `Yes`\|`No`. Displays the widget on pages with product list widgets (e.g., bestseller, new).<br>**Experimental feature**                                                                                                                |
| Enable widget for checkout                     | Value `Yes`\|`No`. Displays the widget on the cart page.                                                                                                                                                                                      |
| Enable widget for minicart                     | Value `Yes`\|`No`. Displays the widget in the cart summary dropdown.                                                                                                                                                                          |
| Enable widget for cart summary                 | Value `Yes`\|`No`. Displays the widget on the cart summary page with the payment method selection.                                                                                                                                            |
| Excluded payTypes                              | Comma-separated list of [payment methods](https://developers.payu.com/europe/docs/get-started/integration-overview/references/#installments-and-pay-later) to omit during widget display. <br> **It is recommended to leave this list empty** |

### "PayU Payment" parameters

| Parameter | Description |
|---------|-----------|
| Activate the plugin? | Determines whether the payment method will be available in the store on the list of payments. |
| Order of payment methods | Determines the order of the payment methods being displayed [more information](#order-of-payment-methods). |
| Activate repeat payment? | [more information](#repeat-payment) |
| Sort Order | Position of the payment method in the list of payment methods. |

### "PayU - Cards" parameters

| Parameter | Description |
|---------|-----------|
| Activate the plugin? | Determines whether the payment method will be available in the store on the list of payments. |
| Activate repeat payment? | [more information](#repeat-payment) |
| Activate remembering of cards? | [more information](#saving-cards) |
| Sort Order | Position of the payment method in the list of payment methods. |

### "PayU - Installments", "PayU - Klarna", "PayU - PayPo", "PayU - PragmaPay", "PayU - Twisto", "PayU - Twisto Pay in 3" payment parameters

| Parameter                           | Description                                                                                 |
|-------------------------------------|---------------------------------------------------------------------------------------------|
| Activate the plugin? | Determines whether the payment method will be available in the store on the list of payments. |
| Activate repeat payment? | [more information](#repeat-payment) |
| Sort Order                          | Position of the payment method in the list of payment methods.                              |


## Information about properties

### Order of payment methods
To determine the order of display of payment method icons indicate the payment method symbols, separating them with a comma. [List of payment methods][ext6].

### Repeat payment
To use this option the POS should be properly configured in PayU and automatic receipt of payments should be disabled (auto-receipt is on by default). 
To do that log in to the PayU panel, go to the tab "Electronic payments", then click "My stores" and POS in the given store. 
The "Automatic payment receipt" option can be found at the bottom under the list of payment methods.

Repeat payment makes it possible to activate multiple payments in PayU to a single order in Magento. 
The plugin will automatically take receipt of the first successful payment while the other ones will be cancelled. 
Repeat payment from the buyer's point of view is also possible through the list of orders in Magento (a link "Pay again" will appear there). 
The buyer will also automatically receive an e-mail with such link. 
Thus, the buyer is able to successfully pay for his order even if the first payment was unsuccessful (for instance, no funds on the card, problems logging in to the bank, etc.).

### Saving cards
Saving card allows logged in users to remember the card for future payments. 
Each such remembered card is "tokenized", however, Magento does not process the card's full details in any way (they are entered using an embedded widget hosted by PayU) and does not save card tokens in its database (before use, current tokens for the given user are always downloaded from PayU).

To ensure proper functioning of the service additional configuration in PayU, consisting in creating and receiving tokens, is required. 
Additionally, the principles of authenticating payments with the remembered card can also be determined (by default every payment with a saved card requires providing a CVV code and being authenticated by 3DS but, for instance, an amount limit up to which this will not be necessary can be defined).

The buyer may save the card while making a payment, using the option "Use and save" on PayU widget while entering the card's details. 
Each card being remembered is subject to strong authentication during first payment (CVV and 3DS). 
A saved card will appear after choosing to pay with a card through PayU for the order and is visible in the user's account (tab "My saved cards"), where an option to delete it is also available.

### Credit widget

To inform customers about credit payment options for a specific product, we recommend placing the credit widget next to products in product lists, in the description (details) of the selected product, in the cart, and at checkout (before payment).
The configuration parameters described in the section ["PayU Credit widget" plugin parameters](#payu-credit-widget-plugin-parameters) allow flexible management of where the credit widget is displayed.

Example presentation of the credit widget

![widget][img1]

<!--external links:-->
[ext0]: https://github.com/PayU-EMEA/plugin_magento
[ext1]: https://www.payu.pl/en/commercial-offer
[ext2]: http://php.net/manual/en/book.curl.php
[ext3]: http://php.net/manual/en/book.hash.php
[ext4]: https://github.com/PayU-EMEA/plugin_magento_23/releases/latest
[ext5]: https://secure.snd.payu.com/boarding/?pk_campaign=Plugin-Github&pk_kwd=Magento2#/form
[ext6]: http://developers.payu.com/pl/overview.html#paymethods
[ext7]: https://github.com/PayU-EMEA/plugin_magento_2
[ext8]: README.md
[ext9]: https://github.com/PayU-EMEA/plugin_magento_23
[ext10]: CHANGELOG.md

<!--images:-->
[img0]: readme_images/methods_en.png
[img1]: readme_images/widget_en.png

