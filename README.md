# 🇪🇹# EthioServe Platform
Ethiopia's #1 All-in-One Super App for Real Estate, Jobs, School Management, Transport, and specialized services.

**Latest Module Status:**
- [x] **Rental Tracking** (Vouchers + Countdown) - ACTIVE
- [x] **School Management System** (Teacher/Student/Parent portals) - INTEGRATED
- [x] **Real-Time Chat** (Typing indicators) - ACTIVE
 
**EthioServe** is a comprehensive Ethiopian services platform offering food delivery, hotel booking, transport/bus booking, house & car rental, flight booking, education resources, and broker management — all in one place.

[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/Mequannent28/Ethioserve)

## 🚀 Features

- 🍽️ **Restaurant & Food Delivery** — Browse menus, order food, and track deliveries
- 🏨 **Hotel Booking** — Find and book hotels across Ethiopia
- 🚌 **Bus Transport** — Book intercity bus tickets with seat selection
- ✈️ **Flight Booking** — Search and book domestic/international flights
- 🏠 **House & Car Rental** — Browse rental listings with images and videos
- 🎓 **Education Portal** — Access textbooks, teacher guides, and video lessons (Grade 1-12)
- 📝 **LMS & Exams** — Take practice exams and track learning progress
- 🚕 **Taxi Services** — Book rides with Ride, Feres, and Yango
- 👤 **Multi-Role System** — Admin, Hotel Owner, Broker, Transport, Taxi, Customer

## 🛠️ Tech Stack

- **Backend:** PHP 8.2
- **Database:** MariaDB (bundled in Docker)
- **Frontend:** HTML, CSS, JavaScript
- **Server:** Apache (with mod_rewrite)
- **Deployment:** Docker (self-contained)

## 📦 Local Development (XAMPP)

1. Clone the repo into your XAMPP `htdocs` folder:
   ```bash
   git clone https://github.com/Mequannent28/Ethioserve.git ethioserve
   ```

2. Create the database:
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create a new database called `ethioserve`
   - Import `database.sql`

3. Access the app:
   ```
   http://localhost/ethioserve
   ```

4. Default login credentials:
   | Role | Username | Password |
   |------|----------|----------|
   | Admin | `admin` | `password` |
   | Hotel | `hilton_owner` | `password` |
   | Broker | `broker1` | `password` |
   | Transport | `golden_bus` | `password` |

## 🌐 Deploy to Render (One Click!)

1. Click the **Deploy to Render** button above
2. Sign in with your GitHub account
3. Click **Create Web Service** — that's it! ✅

The app auto-configures everything:
- ✅ MariaDB database (bundled inside)
- ✅ Schema + seed data (auto-imported)
- ✅ Apache web server (auto-configured)
- ✅ PHP 8.2 with all extensions

Your app will be live at: `https://ethioserve.onrender.com` 🎉

> **Note:** On Render's free tier, the service spins down after 15 minutes of inactivity. The first request after spin-down takes ~30 seconds to start up, and the database is re-seeded with fresh data.

## 📁 Project Structure

```
ethioserve/
├── admin/          # Admin dashboard & management
├── assets/         # CSS, JS, images
├── broker/         # Broker dashboard
├── customer/       # Customer-facing pages
├── hotel/          # Hotel owner dashboard
├── includes/       # Config, DB, header, footer, sidebars
├── restaurant/     # Restaurant owner dashboard
├── taxi/           # Taxi company dashboard
├── transport/      # Transport company dashboard
├── database.sql    # Full database schema + seed data
├── Dockerfile      # Self-contained Docker config
├── render.yaml     # Render Blueprint (auto-deploy)
└── index.php       # Entry point (role-based redirect)
```

## 📄 License

This project is for educational purposes.

---

Made with ❤️ in Ethiopia 🇪🇹
