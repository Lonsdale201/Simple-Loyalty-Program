# Simple Loyalty Program for WooCommerce
This plugin adds customizable loyalty features to WooCommerce, offering various settings for a tailored experience.


> [!IMPORTANT]
> Minimum PHP version: 8.0

> [!IMPORTANT]
> Minimum WordPress version: 6.0

> [!IMPORTANT]
> Minimum WooCommerce version: 9.0.0


> [!CAUTION]
> To use this plugin you need the WooCommerce plugin!

## INSTRUCTIONS

**How to download?**

In the right section the green button: **<>Code** click, and in the dropdown menu, select the **Download ZIP** option. The downloaded file just upload it, you can easily install the downloaded file as an plugin within wordpress.

**Important Note**: The plugin includes a periodic (time-based) discount system. However, this system operates independently from the loyalty program and cannot be used simultaneously. You must choose between using the periodic discount or the loyalty program.

---

## Key Features

- **Periodic Discounts**
- **Loyalty Discounts**
- **Free Gift Products**
- **Exclusion Options**
- **FluentCRM Integration**
- **Notification Options**
- **Inactivity Rules**
- **Free Shipping**

---

## How the Loyalty Program Works

Users are added to the loyalty program when they meet the criteria during a **successful purchase**. The criteria include minimum spending amount, minimum items purchased, and/or user roles. These are evaluated only when an order is completed successfully.

### Stored Data:
- Loyalty status.
- Last activity date.
- Joining date.
- Total spent amount.
- Total item count.

All this data is saved in the user's meta information and used exclusively by the plugin. You can modify a user’s loyalty status directly via the **Admin User Menu** at any time.

---

## Discount System

The plugin provides two types of discounts based on cart contents:

1. **Percentage-Based Discount**  
2. **Fixed Amount Discount**

### Exclusion Options:
- Discounts can be disabled for carts containing sale items.
- Coupons can optionally be disabled when a discount is applied.

### Loyalty Discounts:
- Define criteria for a user to qualify for the loyalty program:
  - Minimum spending amount.
  - Minimum purchased items.
  - User roles.

- Customize the relationship between the criteria:
  - Require at least one condition to be met, or all conditions.

- Automatically assign one or more free gift products to loyalty customers.

---

## Periodic Discount System

This mode offers flexibility with two key options:
- **Target Amount**: Specify the spending threshold that users must reach.
- **Time Frame**: Define the retrospective time period to be considered.

**How it works:**  
Users must spend the target amount within the defined time frame to qualify for discounts.

---

## Additional Features

- Create a custom **"My Account"** menu item for loyalty customers.
- Provide detailed information about the program, including dynamic updates using **smartcodes** for real-time data.

---

## Developer Support

Filters and actions are available for advanced customizations. Detailed documentation on these can be found in the **Wiki** section of this repository.

---

## Roadmap

- [ ] **Rechecker Implementation**: Automate checks for updated eligibility.
- [ ] **Email Notifications**: Notify users about loyalty status or discounts.
- [ ] **Dynamic Shortcodes**: Display live data for advanced customization.

## CHANGELOG

### V 1.1 *2024.12.18*

* The inactivity system did not respect the unique purchase amount and item values (provided by the plugin), and deleted them even if they were not enabled. Fixed
* **New** feature for loyal customers! 

**Free Shipping option**
You can now make **free shipping** to loyal customers. 
Create a free shipping method and select the Loyalty program member option in the requirements field.
You can also combine it with woocommerce's *minimum order amount* setting, because it takes into account.

### V 1.0 *2024.11.18.*

* Initial release
