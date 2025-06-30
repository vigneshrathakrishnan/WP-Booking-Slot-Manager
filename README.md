# MangoCommerce Booking Slot Selector

A lightweight, user-friendly WordPress plugin that lets users select a booking date and time slot on the frontend using a jQuery-powered date picker. Built to work seamlessly with custom post types, and designed for businesses, consultants, or any appointment-based service model.

---

## 🌟 Why This Plugin Exists

Hi, I'm **Vignesh Kumar Radhakrishnan** — a developer driven by the belief that small, thoughtful tools can make a big difference.

This plugin was born out of a need: allowing users to easily select an appointment slot without overwhelming UI or complex dependencies. Paired with a separate admin plugin I built using `CMB2`, this system enables complete slot management for site administrators — all within the familiar WordPress dashboard.

This isn't just a plugin; it’s a piece of my journey. I’m contributing back to the ecosystem, aiming to help real people solve real scheduling problems.

---

## 🔧 Features

* 🗕️ **Frontend Date Picker** (jQuery UI based)
* ⏰ **Time Slot Selector** tailored by weekday
* 🔄 **AJAX-based Slot Rendering** — no reloads
* 🔐 **Role-based Logic** (optional extensions possible)
* 📦 **Minimal Dependencies**, built for clarity and performance
* 🧩 **Works with MangoCommerce Booking Slot Manager Admin Plugin**

  * Admin UI powered by **CMB2**
  * Custom excluded days, durations, and working hours

---

## 🧠 How It Works

This is the **UI module** of a two-part booking system:

1. **Slot Manager Plugin (Admin)**

   * Manage slots, exclusions, working hours
   * Built by extending [CMB2](https://github.com/CMB2/CMB2)

2. **Slot Selector Plugin (This one)**

   * Renders a booking UI
   * Hooks into slot data stored by the admin plugin
   * Outputs a smooth booking experience

---

## 🚀 Usage

1. Upload to `/wp-content/plugins/`

2. Activate the plugin

3. Add the shortcode to any page:

   ```php
   [mango_booking_slot_selector]
   ```

4. Customize via:

   * CSS (`.time-slot`, `.slot-button`, etc.)
   * Filters or action hooks (available in plugin file)

---

## 💡 Developer Notes

* Written with **clean, readable JavaScript**
* Separated logic for data handling and UI
* Handles timezone-aware date logic (e.g., Europe/London offset)
* Easily extendable (you can allow user role validation, custom redirection, WooCommerce integration, etc.)

---

## 👨‍💻 About Me

This plugin is a part of my personal portfolio — built not just to function, but to reflect how I approach problems as a developer.

If you value clarity, purpose, and simplicity in tools, I’d love to connect.

* 🌐 Portfolio: [https://vigneshkumarradhakrishnan.in](https://vigneshkumarradhakrishnan.in)
* 💼 LinkedIn: [https://www.linkedin.com/in/vignesh-kumar-radhakrishnan-4443a416a/](https://www.linkedin.com/in/vignesh-kumar-radhakrishnan-4443a416a/)

---

## 📜 License

**GPLv2** — Free to use, modify, and share.
If it helps someone out there, that's already success in my book.

---

> Let's build tools that are human, purposeful, and elegant — one function at a time.
