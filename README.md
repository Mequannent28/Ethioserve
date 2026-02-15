# 🇪🇹 EthioServe

**EthioServe** is a comprehensive Ethiopian services platform offering food delivery, hotel booking, transport/bus booking, house & car rental, flight booking, education resources, and broker management — all in one place.

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

- **Backend:** PHP 8.x
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript
- **Server:** Apache (with mod_rewrite)
- **Deployment:** Docker (Render)

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

4. Default admin login:
   - Username: `admin`
   - Password: `password`

## 🌐 Deploy to Render

### Step 1: Push to GitHub
```bash
git add .
git commit -m "Ready for Render deployment"
git push origin main
```

### Step 2: Set Up MySQL Database
Render doesn't offer MySQL natively. Use a **free MySQL provider**:

| Provider | Free Tier | Link |
|----------|-----------|------|
| **TiDB Cloud** | 5GB free | [tidbcloud.com](https://tidbcloud.com) |
| **PlanetScale** | 5GB free | [planetscale.com](https://planetscale.com) |
| **Aiven** | Free trial | [aiven.io](https://aiven.io) |
| **Railway** | $5 free credit | [railway.app](https://railway.app) |

After creating the database, import `database.sql` using a MySQL client:
```bash
mysql -h YOUR_HOST -P YOUR_PORT -u YOUR_USER -p YOUR_DB < database.sql
```

### Step 3: Deploy on Render
1. Go to [render.com](https://render.com) and sign in
2. Click **New → Web Service**
3. Connect your GitHub repo: `Mequannent28/Ethioserve`
4. Settings:
   - **Environment:** Docker
   - **Plan:** Free
5. Add **Environment Variables**:
   | Key | Value |
   |-----|-------|
   | `ENVIRONMENT` | `production` |
   | `DB_HOST` | Your MySQL host |
   | `DB_NAME` | Your database name |
   | `DB_USER` | Your database username |
   | `DB_PASS` | Your database password |
   | `DB_PORT` | Your MySQL port (usually 3306) |
   | `BASE_URL` | *(leave empty)* |
6. Click **Create Web Service**

Your app will be live at: `https://ethioserve.onrender.com` 🎉

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
├── Dockerfile      # Docker config for Render
├── render.yaml     # Render Blueprint
└── index.php       # Entry point (role-based redirect)
```

## 📄 License

This project is for educational purposes.

---

Made with ❤️ in Ethiopia 🇪🇹
