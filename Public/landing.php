<?php
// Check if user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff') {
        header('Location: /Campus-Food-Ordering-System/view/admin/admin-dashboard.php');
    } else {
        header('Location: /Campus-Food-Ordering-System/view/customer/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FOODIE - Delicious Food, Delivered Fast</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #0F172A 100%);
        }
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: #10B981;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }
        .btn-outline {
            border: 2px solid #10B981;
            color: #10B981;
            transition: all 0.3s ease;
        }
        .btn-outline:hover {
            background: #10B981;
            color: white;
            transform: translateY(-2px);
        }
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .animate-fade-in {
            animation: fadeIn 1s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <nav class="bg-white/95 backdrop-blur-sm border-b border-slate-100 fixed w-full z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <a href="/Campus-Food-Ordering-System/" class="flex items-center space-x-3 group">
                    <div class="relative flex items-center justify-center text-slate-950">
                        <svg viewBox="0 0 100 100" class="w-11 h-11 fill-current text-slate-950 group-hover:scale-105 transition-transform">
                            <path d="M42,28 C26,28 22,35 22,41 C22,43 23,45 25,45 L59,45 C61,45 62,43 62,41 C62,35 58,28 42,28 Z M22,49 C21,49 20,50 20,51 C20,53 23,55 25,55 L59,55 C61,55 64,53 64,51 C64,50 63,49 62,49 L22,49 Z M25,59 C21,59 21,63 21,65 C21,72 29,76 42,76 C55,76 63,72 63,65 C63,63 63,59 59,59 L25,59 Z" />
                            <path d="M68,20 L80,20 C81,20 82,21 82,22 L76,72 C76,73 75,74 74,74 L64,74 C63,74 62,73 62,72 L65,48 L68,20 Z" />
                            <line x1="74" y1="20" x2="63" y2="8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
                        </svg>
                    </div>
                    <span class="text-2xl font-black tracking-wider text-slate-950">FOODIE</span>
                </a>

                <!-- Navigation Buttons -->
                <div class="flex items-center space-x-4">
                    <a href="/Campus-Food-Ordering-System/view/entrance/login.php" 
                       class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition-colors px-4 py-2">
                        Login
                    </a>
                    <a href="/Campus-Food-Ordering-System/view/entrance/register.php" 
                       class="btn-primary text-white font-bold px-6 py-2.5 rounded-lg text-sm shadow-lg shadow-emerald-500/20">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- ===== HERO SECTION ===== -->
    <section class="hero-gradient min-h-screen flex items-center pt-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="animate-fade-in">
                    <div class="inline-flex items-center space-x-2 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-4 py-1.5 mb-6">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                        <span class="text-xs font-semibold text-emerald-400">Now Delivering</span>
                    </div>
                    
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight">
                        Delicious Food,
                        <span class="text-emerald-400">Delivered Fast</span>
                    </h1>
                    
                    <p class="text-lg text-slate-300 mt-6 max-w-lg">
                        Order your favorite meals from the best restaurants in town. 
                        Fresh, hot, and delivered right to your doorstep.
                    </p>
                    
                    <div class="flex flex-wrap gap-4 mt-8">
                        <a href="/Campus-Food-Ordering-System/view/entrance/register.php" 
                           class="btn-primary text-white font-bold px-8 py-4 rounded-xl text-base shadow-lg shadow-emerald-500/25 inline-flex items-center space-x-2">
                            <span>Order Now</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="/Campus-Food-Ordering-System/view/entrance/login.php" 
                           class="btn-outline font-bold px-8 py-4 rounded-xl text-base inline-flex items-center space-x-2">
                            <span>Login</span>
                        </a>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-6 mt-12 pt-8 border-t border-slate-700">
                        <div>
                            <p class="text-2xl font-extrabold text-white">10K+</p>
                            <p class="text-sm text-slate-400">Happy Customers</p>
                        </div>
                        <div>
                            <p class="text-2xl font-extrabold text-white">50+</p>
                            <p class="text-sm text-slate-400">Food Items</p>
                        </div>
                        <div>
                            <p class="text-2xl font-extrabold text-white">4.8★</p>
                            <p class="text-sm text-slate-400">Average Rating</p>
                        </div>
                    </div>
                </div>
                
                <!-- Right Content - Illustration -->
                <div class="flex justify-center lg:justify-end animate-float">
                    <div class="relative">
                        <!-- Decorative elements -->
                        <div class="absolute -top-10 -left-10 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl"></div>
                        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-purple-500/10 rounded-full blur-2xl"></div>
                        
                        <!-- Food Illustration -->
                        <div class="relative bg-white/5 backdrop-blur-sm rounded-3xl p-8 border border-white/10">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="text-7xl text-center bg-white/5 rounded-2xl p-6 hover:bg-white/10 transition-colors">
                                    🍔
                                </div>
                                <div class="text-7xl text-center bg-white/5 rounded-2xl p-6 hover:bg-white/10 transition-colors">
                                    🍕
                                </div>
                                <div class="text-7xl text-center bg-white/5 rounded-2xl p-6 hover:bg-white/10 transition-colors">
                                    🍜
                                </div>
                                <div class="text-7xl text-center bg-white/5 rounded-2xl p-6 hover:bg-white/10 transition-colors">
                                    🍰
                                </div>
                                <div class="text-7xl text-center bg-white/5 rounded-2xl p-6 hover:bg-white/10 transition-colors">
                                    🥤
                                </div>
                                <div class="text-7xl text-center bg-white/5 rounded-2xl p-6 hover:bg-white/10 transition-colors">
                                    🍣
                                </div>
                            </div>
                            
                            <!-- Floating delivery badge -->
                            <div class="absolute -bottom-4 -right-4 bg-emerald-500 rounded-2xl px-4 py-3 shadow-lg">
                                <div class="flex items-center space-x-2 text-white">
                                    <i class="fa-solid fa-truck-fast text-sm"></i>
                                    <span class="text-sm font-bold">Free Delivery</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FEATURES SECTION ===== -->
    <section class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold text-slate-900">Why Choose <span class="text-emerald-500">FOODIE</span></h2>
                <p class="text-slate-500 mt-4 max-w-2xl mx-auto">We make ordering food simple, fast, and delicious</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="feature-card bg-slate-50 rounded-2xl p-8 text-center">
                    <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-bolt text-2xl text-emerald-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Fast Delivery</h3>
                    <p class="text-slate-500 text-sm mt-2">Get your food delivered hot and fresh within minutes</p>
                </div>
                
                <div class="feature-card bg-slate-50 rounded-2xl p-8 text-center">
                    <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-utensils text-2xl text-emerald-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Diverse Menu</h3>
                    <p class="text-slate-500 text-sm mt-2">Wide variety of dishes from local and international cuisines</p>
                </div>
                
                <div class="feature-card bg-slate-50 rounded-2xl p-8 text-center">
                    <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-star text-2xl text-emerald-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Top Quality</h3>
                    <p class="text-slate-500 text-sm mt-2">Premium ingredients and authentic recipes from expert chefs</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CTA SECTION ===== -->
    <section class="py-16 bg-emerald-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-extrabold text-slate-900">Ready to Order?</h2>
            <p class="text-slate-600 mt-4 max-w-2xl mx-auto">Join thousands of happy customers and start ordering your favorite food today</p>
            <div class="flex flex-wrap justify-center gap-4 mt-8">
                <a href="/Campus-Food-Ordering-System/view/entrance/register.php" 
                   class="btn-primary text-white font-bold px-8 py-4 rounded-xl text-base shadow-lg shadow-emerald-500/25 inline-flex items-center space-x-2">
                    <span>Create Account</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="/Campus-Food-Ordering-System/view/entrance/login.php" 
                   class="bg-white text-slate-700 font-bold px-8 py-4 rounded-xl text-base border border-slate-200 hover:border-emerald-500 hover:text-emerald-600 transition-all inline-flex items-center space-x-2">
                    <span>Login</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="bg-slate-900 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-slate-400 text-sm">&copy; <?php echo date('Y'); ?> FOODIE. All rights reserved. Delicious Food, Delivered Fast.</p>
        </div>
    </footer>

</body>
</html>