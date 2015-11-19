Shipping Quote Module v1.2   >>> UPDATED FOR PRESTASHOP 1.6 <<<

Allows customer requested shipping quote to be manually set in the back office.

SEE: screenshot.png

This source file is subject to the Academic Free License (AFL 3.0)
Designed for PrestaShop™ 1.6
Created by Larry Sacherich 2014-02-02
Based on variableshipping by Gennady Kovshenin for Prestashop 1.5.6.2 

FEATURES:
  - Assign a default Payment Module, Payment Status, and Employee Profile
  - Works with standard 5-page checkout or one-page checkout
  - Emails both customer and designated employee with quote request.
  - Includes any customer comments/messages in the emails.
  - Employee creates order with quote and order is sent to customer 
  - Checks for Terms of Service (if required) before allowing customer to request a quote.
  - Hides Payment options from customer until needed. (Stops customer from paying without quote)
  - Follows closely to Manual Order.
  
  Note: Shipping Quote will only use the first active employee in profile.

BRIEF OVERVIEW OF PROCESS:
  1. Customer requests a shipping quote
  2. Employee receives email with link to customer's cart
  3. Employee clicks "Create an order from this cart"
  4. Determine shipping costs and click "Update Shipping Price"
  5. Send an email to the customer with the link to process the payment
  (You may also want to click "Create the order")

CONFIGURATION:
  To fit your needs, you will need to change some of the following:

  - Localization > Translations 
    Change the shipping cost wording from "Free" or "Free Shipping" to "To be determined"
    About 10 entires                           

  - Shipping > Carriers
    Shipping Quote is normally in last Position
  
  - Shipping > Carriers > Edit Shipping Quote
    See #2: "Shipping locations and costs"
  
  - Shipping > Preferences
    Carrier options 
  
  - Modules and Services > Payment
    Currency restrictions 
    Group restrictions  
    Country restrictions

NOTES: 
  PrestaShop Forums has the installable modules:
  https://www.prestashop.com/forums/index.php?app=core&module=search&do=search&fromMainBar=1&search_term=lsacher
  
  The GitHub style zip files do not install into PrestaShop.
  https://github.com/lsacher/Prestashop-Shipping-Quote
