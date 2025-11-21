# Temperature Monitoring System

Sistem monitoring temperature real-time yang dibangun dengan Laravel sebagai backend dan menggunakan template responsive modern untuk frontend. Sistem ini memungkinkan monitoring temperature berbagai mesin di multiple cabang dengan fitur analisis anomali dan maintenance scheduling.

![Temperature Monitoring System](https://images.pexels.com/photos/159298/gears-cogs-machine-machinery-159298.jpeg?auto=compress&cs=tinysrgb&w=1200&h=400&fit=crop)

## 🌟 Fitur Utama

### 📊 Dashboard & Monitoring
- **Real-time Temperature Monitoring** - Monitoring temperature mesin secara real-time
- **Interactive Dashboard** - Dashboard interaktif dengan visualisasi data
- **Auto-refresh Functionality** - Pembaruan data otomatis setiap 30 detik
- **Alert System** - Sistem notifikasi untuk kondisi anomali

### 🏢 Management System
- **Branch Management** - Kelola berbagai cabang perusahaan
- **Machine Management** - Manajemen data mesin dan equipment
- **Temperature Data Logging** - Pencatatan data temperature historis
- **Maintenance Scheduling** - Penjadwalan maintenance preventif

### 📈 Analytics & Reporting
- **Advanced Analytics** - Analisis mendalam data temperature
- **Anomaly Detection** - Deteksi otomatis kondisi anomali
- **Branch Comparison** - Perbandingan performa antar cabang
- **Data Export** - Export data dan chart dalam format PNG

### 🎨 User Interface
- **Responsive Design** - Tampilan optimal di desktop, tablet, dan mobile
- **Modern UI/UX** - Interface modern dengan gradient design
- **Sidebar Navigation** - Navigasi sidebar yang dapat di-collapse
- **Dark/Light Theme Support** - Dukungan tema terang dan gelap

## 🛠️ Dependencies & Requirements

### Backend Requirements
- **PHP** >= 8.2.4
- **Laravel** >= 10.10
- **MySQL** >= 5.2.1 atau **PostgreSQL** >= 12
- **Composer** untuk dependency management

### Frontend Dependencies
- **Bootstrap** 5.3.0 - Framework CSS untuk responsive design
- **Bootstrap Icons** 1.10.0 - Icon set untuk UI
- **Chart.js** - Library untuk visualisasi chart dan grafik
- **SweetAlert2** 11 - Library untuk notifikasi dan modal yang elegant

### Browser Support
- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+

## 📦 Installation

### 1. Clone Repository
```bash
git clone https://github.com/your-username/temperature-monitoring-system.git
cd temperature-monitoring-system
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies (jika menggunakan Laravel Mix)
npm install
```

### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database di .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=temperature_monitoring
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Database Setup
```bash
# Run migrations
php artisan migrate

# Seed database dengan sample data
php artisan db:seed
```

### 5. Start Development Server
```bash
# Start Laravel server
php artisan serve

# Compile frontend assets (jika menggunakan Laravel Mix)
npm run dev
```

## ⚙️ Configuration

### CSRF Protection
Sistem menggunakan CSRF protection Laravel. Token CSRF secara otomatis di-inject ke dalam template:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### Alert System Configuration
Sistem alert dapat dikonfigurasi melalui JavaScript:

```javascript
// Auto-refresh interval (default: 30 seconds)
setInterval(function () {
    if (document.getElementById('auto-refresh')?.checked) {
        location.reload();
    }
}, 30000);
```

### Temperature Thresholds
Konfigurasi threshold temperature untuk status monitoring:

```javascript
function getTemperatureStatus(temp, minNormal, maxNormal, minCritical, maxCritical) {
    if (temp < minCritical || temp > maxCritical) return 'critical';
    if (temp < minNormal || temp > maxNormal) return 'warning';
    return 'normal';
}
```

## 🎯 Usage

### Dashboard Navigation
- **Dashboard** - Halaman utama dengan overview system
- **Cabang (Branches)** - Manajemen data cabang
- **Mesin (Machines)** - Manajemen data mesin
- **Temperature Data** - View dan manage data temperature
- **Analisis (Analytics)** - Halaman analisis data
- **Anomali (Anomalies)** - Management anomali detection
- **Pemeliharaan (Maintenance)** - Schedule dan tracking maintenance
- **Perbandingan Cabang** - Comparative analysis antar cabang

### Responsive Behavior
- **Desktop (≥992px)**: Sidebar selalu visible, dapat di-collapse
- **Tablet/Mobile (<992px)**: Sidebar hidden by default, slide-in behavior

### Alert Types
Sistem mendukung berbagai jenis alert:
- `success` - Operasi berhasil
- `error` - Error handling
- `warning` - Peringatan system
- `info` - Informasi general

## 📁 File Structure

```
temperature-monitoring-system/
├── app/                          # Laravel application files
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   └── app.blade.php     # Main layout template
│   │   ├── dashboard/            # Dashboard views
│   │   ├── branches/             # Branch management views
│   │   ├── machines/             # Machine management views
│   │   ├── temperature/          # Temperature data views
│   │   ├── analytics/            # Analytics views
│   │   ├── anomalies/            # Anomaly management views
│   │   └── maintenance/          # Maintenance views
│   ├── css/                      # Custom CSS files
│   └── js/                       # Custom JavaScript files
├── public/                       # Public assets
├── routes/                       # Application routes
├── database/                     # Database files
└── README.md                     # This file
```

## 🎨 Customization

### CSS Variables
Template menggunakan CSS custom properties yang dapat dikustomisasi:

```css
:root {
    --sidebar-width: 280px;
    --navbar-height: 56px;
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --secondary-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --danger-gradient: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}
```

### Color Scheme
Sistem menggunakan color coding untuk status:
- **Normal**: Green (#198754)
- **Warning**: Orange (#fd7e14) 
- **Critical**: Red (#dc3545)

### Adding New Charts
Untuk menambah chart baru menggunakan Chart.js:

```javascript
// Example chart configuration
const ctx = document.getElementById('myChart').getContext('2d');
const myChart = new Chart(ctx, {
    type: 'line',
    data: {
        // Your data here
    },
    options: {
        // Chart options
    }
});
```

## 🔧 Advanced Features

### Download Chart Functionality
```javascript
function downloadChart(chartId, filename) {
    const canvas = document.getElementById(chartId);
    if (canvas) {
        const link = document.createElement('a');
        link.download = filename + '_' + new Date().toISOString().slice(0, 10) + '.png';
        link.href = canvas.toDataURL();
        link.click();
    }
}
```

### SweetAlert Integration
Template mengintegrasikan SweetAlert2 untuk user experience yang lebih baik:

```javascript
// Success notification
function showSuccessToast(message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
    Toast.fire({
        icon: 'success',
        title: message
    });
}
```

## 🚀 Deployment

### Production Deployment
1. **Optimize untuk production**:
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Set environment variables**:
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   ```

3. **Configure web server** (Apache/Nginx) untuk serve Laravel application

### Docker Deployment
```dockerfile
# Example Dockerfile
FROM php:8.1-fpm

# Install dependencies and configure PHP
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip

# Copy application files
COPY . /var/www/html
WORKDIR /var/www/html

# Install composer dependencies
RUN composer install --optimize-autoloader --no-dev
```

## 🤝 Contributing

1. Fork repository ini
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

### Coding Standards
- Follow PSR-12 untuk PHP code
- Use semantic commit messages
- Write comprehensive tests untuk new features
- Maintain responsive design principles

## 📝 API Documentation

### Temperature Data Endpoints
```
GET    /api/temperature              # Get temperature data
POST   /api/temperature              # Store temperature reading
PUT    /api/temperature/{id}         # Update temperature record
DELETE /api/temperature/{id}         # Delete temperature record
```

### Analytics Endpoints
```
GET    /api/analytics/overview       # Get analytics overview
GET    /api/analytics/trends         # Get temperature trends
GET    /api/analytics/anomalies      # Get anomaly data
```

## 🐛 Troubleshooting

### Common Issues

**Issue**: Sidebar tidak responsive di mobile
```javascript
// Solution: Check window resize event listener
window.addEventListener('resize', function() {
    if (window.innerWidth >= 992) {
        // Desktop logic
    } else {
        // Mobile logic
        mainContent.classList.add('expanded');
    }
});
```

**Issue**: Chart tidak loading
```javascript
// Solution: Ensure Chart.js is loaded before initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts here
});
```

**Issue**: CSRF token mismatch
```javascript
// Solution: Include CSRF token in AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

## 📊 Performance Tips

1. **Enable Laravel caching**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Optimize database queries** dengan Eloquent relationships

3. **Use pagination** untuk large datasets

4. **Implement caching** untuk frequently accessed data

5. **Minify CSS/JS** files untuk production

## 📄 License

Distributed under the MIT License. See `LICENSE` file for more information.

## 👥 Authors & Contributors

- **Gezod** - *Initial work* - [YourGithub](https://github.com/Gezod)

## 🙏 Acknowledgments

- [Laravel](https://laravel.com/) - PHP framework
- [Bootstrap](https://getbootstrap.com/) - CSS framework
- [Chart.js](https://www.chartjs.org/) - Chart library
- [SweetAlert2](https://sweetalert2.github.io/) - Beautiful alerts
- [Bootstrap Icons](https://icons.getbootstrap.com/) - Icon library

## 📞 Support

Jika Anda menemukan bug atau memiliki feature request, silakan buat issue di GitHub repository atau hubungi:

- Email: refanggalintar@gmail.com
- Documentation: [Wiki](https://github.com/your-username/temperature-monitoring-system/wiki)
- Community: [Discussions](https://github.com/your-username/temperature-monitoring-system/discussions)

---

**Temperature Monitoring System** - Monitoring temperature equipment dengan teknologi modern untuk operational excellence.
