Build and refine a PHP/MySQL e‑commerce web app (XAMPP, PDO) with user and admin portals. Users can register/login, browse/search products, view product details, manage cart and wishlist, checkout with address form, and place orders. Admins can log in, view a modern dashboard with quick stats, badges, and Chart.js visualizations (sales overview and top users), manage products, users, orders (update status, delete), stock, and messages.

Key requirements:
Tech: PHP 7+/PDO, MySQL, HTML/CSS/JS, Font Awesome, Chart.js; session-based auth; secure queries.
Data: tables for users, admins, products, cart, wishlist, orders, messages, stock_alerts, stock_history; indexes for performance.
Admin dashboard: left panel nav with badges; quick stats (pending/completed totals); sales doughnut and top users bar charts; stock alerts panel with unread/low/out-of-stock counts and recent alerts; link to full alerts page.
Stock management: product stock_quantity/min_stock_level/stock_status; on order placement, decrement stock, update status (in_stock/low_stock/out_of_stock), log stock_history, raise stock_alerts; ability to mark/read/clear alerts; alerts list filtered to active issues.

Pages:
User: home, shop, category, search, quick_view, cart, wishlist, checkout, orders, update_user, contact; user_header with session/cart state.

Admin: dashboard, placed_orders (horizontal responsive table), products (CRUD), users_accounts (horizontal responsive table), messages, stock_alerts, stock_management, update_profile.

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
All listed pages rendering without PHP notices and with graceful empty states


more info- maharjansujal0@gmail.com
whatsapp:9840444737
