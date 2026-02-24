Build and refine a PHP/MySQL e‑commerce web app (XAMPP, PDO) with user and admin portals. Users can register/login, browse/search products, view product details, manage cart and wishlist, checkout with address form, and place orders. Admins can log in, view a modern dashboard with quick stats, badges, and Chart.js visualizations (sales overview and top users), manage products, users, orders (update status, delete), stock, and messages.

## Live Demo
Production URL:  
[https://yourdomain.infinityfreeapp.co](https://sujalnexus.rf.gd/?i=1)

Key requirements:
Tech: PHP 7+/PDO, MySQL, HTML/CSS/JS, Font Awesome, Chart.js; session-based auth; secure queries.
Data: tables for users, admins, products, cart, wishlist, orders, messages, stock_alerts, stock_history; indexes for performance.
Admin dashboard: left panel nav with badges; quick stats (pending/completed totals); sales doughnut and top users bar charts; stock alerts panel with unread/low/out-of-stock counts and recent alerts; link to full alerts page.
Stock management: product stock_quantity/min_stock_level/stock_status; on order placement, decrement stock, update status (in_stock/low_stock/out_of_stock), log stock_history, raise stock_alerts; ability to mark/read/clear alerts; alerts list filtered to active issues.

Pages:
User: home, shop, category, search, quick_view, cart, wishlist, checkout, orders, update_user, contact; user_header with session/cart state.
# Screenshots
Landing Page
<img width="1917" height="984" alt="image" src="https://github.com/user-attachments/assets/18904bce-15f2-4764-b1c0-7c9e9dc7577e" />
<img width="1918" height="988" alt="image" src="https://github.com/user-attachments/assets/f387ef47-b1e7-4c47-b275-3d4319e6cbd3" />

Shop now Page
<img width="1908" height="972" alt="image" src="https://github.com/user-attachments/assets/6a29d4e8-12ad-4cf0-8b6f-84c26da712bb" />

Order Page
<img width="1917" height="945" alt="image" src="https://github.com/user-attachments/assets/30d34c6e-2b69-432a-ab8a-f0d4a2f2f917" />

Cart Page
<img width="1914" height="950" alt="image" src="https://github.com/user-attachments/assets/3a64baab-b79e-4303-bdbe-145b620ddbaa" />

Checkout Page
<img width="1916" height="950" alt="image" src="https://github.com/user-attachments/assets/75fbfaf3-05f6-4f01-9ed5-389781f99c80" />

Quick View Page
<img width="1917" height="949" alt="image" src="https://github.com/user-attachments/assets/4af64aa6-17a1-4c4a-840f-caf03c20a7a0" />

Admin: dashboard, placed_orders (horizontal responsive table), products (CRUD), users_accounts (horizontal responsive table), messages, stock_alerts, stock_management, update_profile.

Admin Dashboard 
<img width="1914" height="948" alt="image" src="https://github.com/user-attachments/assets/58124539-179c-4bf4-96d6-f4b1e946b6a7" />

Stock Alert
<img width="1910" height="948" alt="image" src="https://github.com/user-attachments/assets/45c4db71-867d-4e9c-9157-7fe9830673bb" />

Order Management Page
<img width="1905" height="950" alt="image" src="https://github.com/user-attachments/assets/0d82c1c9-1164-4b1b-85e9-9d0f63885f6c" />

User Management 
<img width="1903" height="943" alt="image" src="https://github.com/user-attachments/assets/34a5b56a-1c99-4207-bac0-31987a042f6f" />

UI/UX: modern gradient styling, badges, responsive tables for orders/users, hover/transition effects, pulse on alert badge, consistent typography.

Security: session checks, CSRF token on forms, input validation/sanitization, prepared statements everywhere.

Checkout: validate name/phone/email/address; verify cart totals and stock before placing order; generate friendly messages; for online payment, integrate eSewa (sandbox/production), HMAC signature over required fields, success/failure callbacks and server-side verification before creating finalized paid orders. Keep Cash on Delivery flow intact.

Reporting: basic counts (orders/users/products/messages), top spenders chart, pending vs completed totals.

Code quality: readable names, guard clauses, minimal nesting, no inline secrets, cobnsistent formatting, no unused code, no SQL in views except prepared statements.

Deliverables:
Fully working app under C:\xampp\htdocs\projectdone
Clean PHP files per current structure
CSS in css/style.css and css/admin_style.css
JS in js/script.js and js/admin_script.js
SQL schema/setup in database_setup.sql
Admin and user navigation wired with correct routes and session protection
All listed pagess rendering without PHP notices and with graceful empty states

## Installation Guide

1. Clone the repository:
https://github.com/suzzzel5/Ecommerce-webiste-for-Bag-store.git

2. Move the project folder into your web server directory (e.g., htdocs for XAMPP).

3. Create a new database in phpMyAdmin.

4. Import the provided SQL file into the database.

5. Update database credentials in:

```
config.php
```

6. Start Apache and MySQL.

7. Open in browser:

```
http://localhost/nexus-bag
```

---


more info- maharjansujal0@gmail.com
whatsapp:9840444737
