<?php
require_once 'config.php';

// Fetch medicines from database with online images
$query = "SELECT * FROM medicines WHERE stock > 0 ORDER BY 
          CASE 
            WHEN featured = 1 THEN 1 
            ELSE 2 
          END, created_at DESC LIMIT 12";
$result = mysqli_query($conn, $query);

// Fetch categories for filter
$categories_query = "SELECT DISTINCT category FROM medicines WHERE stock > 0";
$categories_result = mysqli_query($conn, $categories_query);

// Fetch special offers
$offers_query = "SELECT * FROM medicines WHERE discount > 0 AND stock > 0 ORDER BY discount DESC LIMIT 4";
$offers_result = mysqli_query($conn, $offers_query);
?>

<?php include 'header.php'; ?>

<!-- Hero Section with 3D Parallax Slideshow -->
<section class="hero-3d-slideshow">
    <div class="slideshow-container">
        <!-- Slide 1 -->
        <div class="hero-slide active" id="slide1">
            <div class="slide-bg p<!-- Replace the existing buy form with this -->
<?php if (isLoggedIn() && !isAdmin()): ?>
    <div class="product-actions">
        <div class="quantity-selector">
            <button type="button" class="qty-btn" onclick="decrementQty(this)">−</button>
            <input type="number" class="qty-input" id="qty-<?php echo $medicine['id']; ?>" value="1" min="1" max="<?php echo $medicine['stock']; ?>">
            <button type="button" class="qty-btn" onclick="incrementQty(this, <?php echo $medicine['stock']; ?>)">+</button>
        </div>
        
        <div class="action-buttons">
            <button class="add-to-cart-btn" onclick="addToCart(<?php echo $medicine['id']; ?>, document.getElementById('qty-<?php echo $medicine['id']; ?>').value)">
                <span class="btn-icon">🛒</span>
                Add to Cart
            </button>
            
            <button class="buy-now-btn" onclick="buyNow(<?php echo $medicine['id']; ?>, document.getElementById('qty-<?php echo $medicine['id']; ?>').value)">
                <span class="btn-icon">💰</span>
                Buy Now
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Add this JavaScript -->
<script>
function decrementQty(btn) {
    const input = btn.parentElement.querySelector('.qty-input');
    let value = parseInt(input.value);
    if (value > 1) {
        input.value = value - 1;
    }
}

function incrementQty(btn, max) {
    const input = btn.parentElement.querySelector('.qty-input');
    let value = parseInt(input.value);
    if (value < max) {
        input.value = value + 1;
    }
}

function buyNow(medicineId, quantity) {
    // Create a form and submit to buy.php
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'buy.php';
    
    const medicineInput = document.createElement('input');
    medicineInput.type = 'hidden';
    medicineInput.name = 'medicine_id';
    medicineInput.value = medicineId;
    
    const quantityInput = document.createElement('input');
    quantityInput.type = 'hidden';
    quantityInput.name = 'quantity';
    quantityInput.value = quantity;
    
    form.appendChild(medicineInput);
    form.appendChild(quantityInput);
    document.body.appendChild(form);
    form.submit();
}
</script>

<!-- Add CSS for the new buttons -->
<style>
.product-actions {
    margin-top: 1rem;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    margin-bottom: 0.8rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.add-to-cart-btn, .buy-now-btn {
    flex: 1;
    padding: 0.6rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    transition: all 0.3s;
}

.add-to-cart-btn {
    background: #3b82f6;
    color: white;
}

.add-to-cart-btn:hover {
    background: #2563eb;
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(59,130,246,0.4);
}

.buy-now-btn {
    background: #059669;
    color: white;
}

.buy-now-btn:hover {
    background: #047857;
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(5,150,105,0.4);
}

.btn-icon {
    font-size: 1rem;
}
</style>arallax" style="background-image: url('https://images.unsplash.com/photo-1576671081837-49000212a370?ixlib=rb-1.2.1&auto=format&fit=crop&1950x1080');"></div>
            <div class="slide-content-3d">
                <span class="slide-subtitle animate-slide-up">Welcome to</span>
                <h1 class="slide-title animate-slide-up">🏥📌<span class="gold-text">GOLD</span> Health</h1>
                <p class="slide-description animate-slide-up">Your Trusted Partner in Healthcare Excellence</p>
                <div class="slide-cta animate-slide-up">
                    <a href="#medicines" class="cta-primary">Shop Now <i class="fas fa-arrow-right"></i></a>
                    <a href="#offers" class="cta-secondary">View Offers</a>
                </div>
            </div>
        </div>
        
        <!-- Slide 2 -->
        <div class="hero-slide" id="slide2">
            <div class="slide-bg parallax" style="background-image: url('https://images.unsplash.com/photo-1631549916768-4119b2e5f926?ixlib=rb-1.2.1&auto=format&fit=crop&1950x1080');"></div>
            <div class="slide-content-3d">
                <span class="slide-subtitle animate-slide-up">Mobile Money Made Easy</span>
                <h1 class="slide-title animate-slide-up">Pay with <span class="mtn-text">MTN</span> or <span class="airtel-text">Airtel</span></h1>
                <p class="slide-description animate-slide-up">Fast, Secure & Convenient Payments</p>
                <div class="payment-icons animate-slide-up">
                    <img src="https://cdn-icons-png.flaticon.com/512/3030/3030247.png" alt="MTN" class="payment-icon pulse">
                    <img src="https://cdn-icons-png.flaticon.com/512/3030/3030251.png" alt="Airtel" class="payment-icon pulse">
                </div>
            </div>
        </div>
        
        <!-- Slide 3 -->
        <div class="hero-slide" id="slide3">
            <div class="slide-bg parallax" style="background-image: url('https://images.unsplash.com/photo-1587854692152-cbe660dbde88?ixlib=rb-1.2.1&auto=format&fit=crop&1950x1080');"></div>
            <div class="slide-content-3d">
                <span class="slide-subtitle animate-slide-up">Special Offer</span>
                <h1 class="slide-title animate-slide-up">Free Delivery</h1>
                <p class="slide-description animate-slide-up">On orders above RWANDA 50,000 within Kigali</p>
                <div class="offer-badge animate-bounce">Limited Time</div>
            </div>
        </div>
    </div>
    
    <!-- Slideshow Controls -->
    <div class="slideshow-controls">
        <button class="slide-control prev" onclick="changeSlide(-1)">❮</button>
        <button class="slide-control next" onclick="changeSlide(1)">❯</button>
    </div>
    
    <!-- Slide Indicators -->
    <div class="slide-indicators">
        <span class="indicator active" onclick="goToSlide(0)"></span>
        <span class="indicator" onclick="goToSlide(1)"></span>
        <span class="indicator" onclick="goToSlide(2)"></span>
    </div>
</section>

<!-- Floating Promotion Banner -->
<div class="floating-promo" id="floatingPromo">
    <div class="promo-content">
        <button class="close-promo" onclick="closePromo()">×</button>
        <div class="promo-gif">
            <img src="https://cdn-icons-png.flaticon.com/512/3004/3004488.png" alt="Special Offer" class="rotating">
        </div>
        <div class="promo-text">
            <h3>🎉 Flash Sale!</h3>
            <p>Up to 40% off on Vitamins</p>
            <div class="promo-timer" id="flashSaleTimer"></div>
            <a href="#offers" class="promo-btn" onclick="filterCategory('Vitamins')">Shop Now</a>
        </div>
    </div>
</div>

<!-- Advertising Banner Carousel -->
<section class="advertising-carousel">
    <div class="carousel-container">
        <div class="carousel-track" id="adCarousel">
            <!-- Ad 1 -->
            <div class="ad-card">
                <img src="https://images.unsplash.com/photo-1584017911766-451b3d0e8434?ixlib=rb-1.2.1&auto=format&fit=crop&400x300" alt="Vitamin Sale">
                <div class="ad-overlay">
                    <h3>Vitamin C Special</h3>
                    <p>Buy 2 Get 1 Free</p>
                    <span class="ad-discount">30% OFF</span>
                </div>
            </div>
            
            <!-- Ad 2 -->
            <div class="ad-card">
                <img src="https://images.unsplash.com/photo-1550574697-7d776f405ed2?ixlib=rb-1.2.1&auto=format&fit=crop&400x300" alt="Pain Relief">
                <div class="ad-overlay">
                    <h3>Pain Relief Range</h3>
                    <p>Starting at RWANDA 5,000</p>
                    <span class="ad-discount">20% OFF</span>
                </div>
            </div>
            
            <!-- Ad 3 -->
            <div class="ad-card">
                <img src="https://images.unsplash.com/photo-1471864190281-a93a3070b6de?ixlib=rb-1.2.1&auto=format&fit=crop&400x300" alt="Antibiotics">
                <div class="ad-overlay">
                    <h3>Antibiotics</h3>
                    <p>Prescription Available</p>
                    <span class="ad-tag">Consult Now</span>
                </div>
            </div>
            
            <!-- Ad 4 -->
            <div class="ad-card">
                <img src="https://images.unsplash.com/photo-1628771065518-0d82f1938462?ixlib=rb-1.2.1&auto=format&fit=crop&400x300" alt="Cough & Cold">
                <div class="ad-overlay">
                    <h3>Cough & Cold</h3>
                    <p>Fast Relief</p>
                    <span class="ad-discount">15% OFF</span>
                </div>
            </div>
            
            <!-- Ad 5 -->
            <div class="ad-card">
                <img src="https://images.unsplash.com/photo-1631549916768-4119b2e5f926?ixlib=rb-1.2.1&auto=format&fit=crop&400x300" alt="Allergy Relief">
                <div class="ad-overlay">
                    <h3>Allergy Relief</h3>
                    <p>Seasonal Special</p>
                    <span class="ad-discount">25% OFF</span>
                </div>
            </div>
        </div>
        
        <!-- Carousel Navigation -->
        <button class="carousel-btn prev" onclick="moveCarousel(-1)">❮</button>
        <button class="carousel-btn next" onclick="moveCarousel(1)">❯</button>
    </div>
    
    <!-- Dots Indicator -->
    <div class="carousel-dots" id="carouselDots"></div>
</section>

<!-- Features with 3D Icons -->
<section class="features-3d">
    <div class="feature-3d-card" data-tilt>
        <div class="feature-icon-3d">
            <img src="https://cdn-icons-png.flaticon.com/512/3063/3063825.png" alt="Fast Delivery" class="float-3d">
            <div class="icon-glow"></div>
        </div>
        <h3>Lightning Fast Delivery</h3>
        <p>Same day delivery within Kampala</p>
        <div class="feature-stats">2-4 hrs</div>
    </div>
    
    <div class="feature-3d-card" data-tilt>
        <div class="feature-icon-3d">
            <img src="https://cdn-icons-png.flaticon.com/512/2945/2945478.png" alt="Quality" class="float-3d">
            <div class="icon-glow"></div>
        </div>
        <h3>100% Genuine</h3>
        <p>Certified medicines only</p>
        <div class="feature-stats">NAFDAC Approved</div>
    </div>
    
    <div class="feature-3d-card" data-tilt>
        <div class="feature-icon-3d">
            <img src="https://cdn-icons-png.flaticon.com/512/3030/3030247.png" alt="MTN Money" class="float-3d">
            <div class="icon-glow"></div>
        </div>
        <h3>MTN & Airtel Money</h3>
        <p>Secure mobile payments</p>
        <div class="feature-stats">Instant Confirmation</div>
    </div>
    
    <div class="feature-3d-card" data-tilt>
        <div class="feature-icon-3d">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Support" class="float-3d">
            <div class="icon-glow"></div>
        </div>
        <h3>24/7 Support</h3>
        <p>Always here to help</p>
        <div class="feature-stats">Live Chat</div>
    </div>
</section>

<!-- Special Offers Section -->
<section id="offers" class="special-offers">
    <h2 class="section-title">🔥 Hot Deals <span class="title-shimmer">Today Only</span></h2>
    
    <div class="offers-grid">
        <?php if (mysqli_num_rows($offers_result) > 0): ?>
            <?php while ($offer = mysqli_fetch_assoc($offers_result)): ?>
                <div class="offer-card glass-effect">
                    <div class="offer-badge">-<?php echo $offer['discount']; ?>%</div>
                    <div class="offer-image">
                        <img src="<?php echo getOnlineMedicineImage($offer['name']); ?>" 
                             alt="<?php echo $offer['name']; ?>"
                             class="offer-img">
                        <div class="offer-timer" data-expiry="2024-12-31"></div>
                    </div>
                    <div class="offer-details">
                        <h3><?php echo $offer['name']; ?></h3>
                        <div class="offer-price">
                            <span class="old-price">RW <?php echo number_format($offer['price'] * 3700, 0); ?></span>
                            <span class="new-price">RW <?php echo number_format(($offer['price'] * (100 - $offer['discount']) / 100) * 3700, 0); ?></span>
                        </div>
                        <button class="offer-btn" onclick="addToCart(<?php echo $offer['id']; ?>)">Grab Deal</button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Category Showcase -->
<section class="category-showcase">
    <h2 class="section-title">Shop by <span class="gradient-text">Category</span></h2>
    
    <div class="category-3d-grid">
        <div class="category-3d-item" onclick="filterByCategory('Pain Relief')">
            <div class="category-3d-inner">
                <div class="category-3d-front">
                    <img src="https://cdn-icons-png.flaticon.com/512/3004/3004458.png" alt="Pain Relief">
                    <h3>Pain Relief</h3>
                </div>
                <div class="category-3d-back">
                    <p>Analgesics & Anti-inflammatory</p>
                    <span>Shop Now →</span>
                </div>
            </div>
        </div>
        
        <div class="category-3d-item" onclick="filterByCategory('Antibiotics')">
            <div class="category-3d-inner">
                <div class="category-3d-front">
                    <img src="https://cdn-icons-png.flaticon.com/512/3050/3050225.png" alt="Antibiotics">
                    <h3>Antibiotics</h3>
                </div>
                <div class="category-3d-back">
                    <p>Prescription Antibiotics</p>
                    <span>Shop Now →</span>
                </div>
            </div>
        </div>
        
        <div class="category-3d-item" onclick="filterByCategory('Vitamins')">
            <div class="category-3d-inner">
                <div class="category-3d-front">
                    <img src="https://cdn-icons-png.flaticon.com/512/3004/3004488.png" alt="Vitamins">
                    <h3>Vitamins</h3>
                </div>
                <div class="category-3d-back">
                    <p>Supplements & Minerals</p>
                    <span>Shop Now →</span>
                </div>
            </div>
        </div>
        
        <div class="category-3d-item" onclick="filterByCategory('Cold & Flu')">
            <div class="category-3d-inner">
                <div class="category-3d-front">
                    <img src="https://cdn-icons-png.flaticon.com/512/3004/3004471.png" alt="Cold & Flu">
                    <h3>Cold & Flu</h3>
                </div>
                <div class="category-3d-back">
                    <p>Cough Syrups & Decongestants</p>
                    <span>Shop Now →</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Medicines Grid with Filters -->
<section id="medicines" class="products-section">
    <div class="section-header">
        <h2 class="section-title">Our <span class="gradient-text">Medicines</span></h2>
        
        <!-- Search and Filter Bar -->
        <div class="filter-bar glass-effect">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search medicines..." onkeyup="searchMedicines()">
                <i class="search-icon">🔍</i>
            </div>
            
            <select id="categoryFilter" onchange="filterMedicines()" class="filter-select">
                <option value="">All Categories</option>
                <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                    <option value="<?php echo $category['category']; ?>"><?php echo $category['category']; ?></option>
                <?php endwhile; ?>
            </select>
            
            <select id="sortFilter" onchange="sortMedicines()" class="filter-select">
                <option value="default">Sort By</option>
                <option value="price-low">Price: Low to High</option>
                <option value="price-high">Price: High to Low</option>
                <option value="name">Name</option>
                <option value="popular">Most Popular</option>
            </select>
        </div>
    </div>
    
    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="products-grid" id="productsGrid">
            <?php 
            mysqli_data_seek($result, 0);
            while ($medicine = mysqli_fetch_assoc($result)): 
            ?>
                <div class="product-card" 
                     data-category="<?php echo $medicine['category']; ?>"
                     data-name="<?php echo strtolower($medicine['name']); ?>"
                     data-price="<?php echo $medicine['price']; ?>"
                     data-popularity="<?php echo rand(1, 100); ?>">
                    
                    <div class="product-badges">
                        <?php if ($medicine['discount'] > 0): ?>
                            <span class="badge discount">-<?php echo $medicine['discount']; ?>%</span>
                        <?php endif; ?>
                        <?php if ($medicine['featured'] == 1): ?>
                            <span class="badge featured">Featured</span>
                        <?php endif; ?>
                        <?php if ($medicine['stock'] < 10): ?>
                            <span class="badge low-stock">Low Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-image">
                        <img src="<?php echo getOnlineMedicineImage($medicine['name']); ?>" 
                             alt="<?php echo $medicine['name']; ?>"
                             class="product-img"
                             loading="lazy">
                        
                        <!-- Quick View Overlay -->
                        <div class="quick-view-overlay">
                            <button class="quick-view-btn" onclick="showQuickView(<?php echo $medicine['id']; ?>)">Quick View</button>
                        </div>
                    </div>
                    
                    <div class="product-info">
                        <h3 class="product-name"><?php echo $medicine['name']; ?></h3>
                        <p class="product-desc"><?php echo substr($medicine['description'], 0, 50); ?>...</p>
                        
                        <div class="product-rating">
                            <?php 
                            $rating = $medicine['rating'] ?? 4;
                            for($i = 1; $i <= 5; $i++): 
                                if($i <= $rating): 
                            ?>
                                <span class="star filled">★</span>
                            <?php else: ?>
                                <span class="star">☆</span>
                            <?php endif; endfor; ?>
                            <span class="rating-count">(<?php echo rand(10, 100); ?>)</span>
                        </div>
                        
                        <div class="product-price-section">
                            <?php if ($medicine['discount'] > 0): ?>
                                <span class="original-price">UGX <?php echo number_format($medicine['price'] * 3700, 0); ?></span>
                                <span class="discounted-price">
                                    UGX <?php echo number_format(($medicine['price'] * (100 - $medicine['discount']) / 100) * 3700, 0); ?>
                                </span>
                            <?php else: ?>
                                <span class="current-price">UGX <?php echo number_format($medicine['price'] * 3700, 0); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="stock-status">
                            <span class="stock-indicator <?php echo $medicine['stock'] > 10 ? 'in-stock' : 'low-stock'; ?>"></span>
                            <span><?php echo $medicine['stock']; ?> units available</span>
                        </div>
                        
                        <?php if (isLoggedIn() && !isAdmin()): ?>
                            <form action="buy.php" method="POST" class="purchase-form" onsubmit="return validatePurchase(this)">
                                <input type="hidden" name="medicine_id" value="<?php echo $medicine['id']; ?>">
                                
                                <div class="quantity-wrapper">
                                    <button type="button" class="qty-decrease" onclick="decrementQuantity(this)">−</button>
                                    <input type="number" name="quantity" class="qty-input" value="1" min="1" max="<?php echo $medicine['stock']; ?>" readonly>
                                    <button type="button" class="qty-increase" onclick="incrementQuantity(this, <?php echo $medicine['stock']; ?>)">+</button>
                                </div>
                                
                                <div class="payment-options">
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="MTN" checked>
                                        <img src="https://cdn-icons-png.flaticon.com/512/3030/3030247.png" alt="MTN">
                                        <span>MTN</span>
                                    </label>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="AIRTEL">
                                        <img src="https://cdn-icons-png.flaticon.com/512/3030/3030251.png" alt="Airtel">
                                        <span>Airtel</span>
                                    </label>
                                </div>
                                
                                <button type="submit" class="buy-now-btn">
                                    <span>Buy Now</span>
                                    <span class="btn-shine"></span>
                                </button>
                            </form>
                        <?php elseif (!isLoggedIn()): ?>
                            <div class="login-prompt">
                                <a href="login.php" class="login-link">Login to Purchase</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-products">
            <img src="https://cdn-icons-png.flaticon.com/512/4076/4076478.png" alt="No products">
            <h3>No medicines available</h3>
            <p>Check back later for updates</p>
        </div>
    <?php endif; ?>
</section>

<!-- Testimonials Section -->
<section class="testimonials">
    <h2 class="section-title">What Our <span class="gradient-text">Customers Say</span></h2>
    
    <div class="testimonial-carousel">
        <div class="testimonial-track" id="testimonialTrack">
            <div class="testimonial-card">
                <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-1.2.1&auto=format&fit=crop&100x100" alt="Customer">
                <div class="testimonial-content">
                    <p>"Excellent service! Delivered within 2 hours. Highly recommended!"</p>
                    <h4>Sarah Namutebi</h4>
                    <div class="rating">★★★★★</div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-1.2.1&auto=format&fit=crop&100x100" alt="Customer">
                <div class="testimonial-content">
                    <p>"MTN payment was seamless. Got my medicines within hours."</p>
                    <h4>John Okello</h4>
                    <div class="rating">★★★★★</div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <img src="https://images.unsplash.com/photo-1494790108777-466fd0c77e6c?ixlib=rb-1.2.1&auto=format&fit=crop&100x100" alt="Customer">
                <div class="testimonial-content">
                    <p>"Great prices and authentic products. My go-to pharmacy!"</p>
                    <h4>Mary Akello</h4>
                    <div class="rating">★★★★★</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Quick View Modal -->
<div id="quickViewModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeQuickView()">&times;</span>
        <div id="quickViewContent"></div>
    </div>
</div>

<!-- Add to Cart Animation -->
<div class="add-to-cart-animation" id="cartAnimation">
    <div class="animation-content">
        <div class="checkmark-circle">
            <div class="checkmark"></div>
        </div>
        <p>Added to Cart!</p>
    </div>
</div>

<style>
/* Hero 3D Slideshow */
.hero-3d-slideshow {
    position: relative;
    height: 600px;
    overflow: hidden;
    perspective: 1000px;
}

.slideshow-container {
    position: relative;
    width: 100%;
    height: 100%;
    transform-style: preserve-3d;
}

.hero-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    visibility: hidden;
    transition: all 1.5s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateZ(-100px) rotateY(10deg);
}

.hero-slide.active {
    opacity: 1;
    visibility: visible;
    transform: translateZ(0) rotateY(0);
}

.slide-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    filter: brightness(0.7);
    transform: scale(1.1);
    transition: transform 10s linear;
}

.active .slide-bg {
    transform: scale(1);
}

.slide-content-3d {
    position: relative;
    z-index: 2;
    max-width: 800px;
    margin: 0 auto;
    padding: 120px 20px;
    color: white;
    text-align: center;
    transform-style: preserve-3d;
}

.slide-subtitle {
    display: inline-block;
    font-size: 1.2rem;
    text-transform: uppercase;
    letter-spacing: 3px;
    margin-bottom: 1rem;
    background: rgba(255,255,255,0.2);
    padding: 0.5rem 1.5rem;
    border-radius: 30px;
    backdrop-filter: blur(5px);
}

.slide-title {
    font-size: 4rem;
    font-weight: 800;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.gold-text {
    color: #FFD700;
    text-shadow: 0 0 20px rgba(255,215,0,0.5);
}

.mtn-text {
    color: #ffcc00;
    text-shadow: 0 0 20px rgba(255,204,0,0.5);
}

.airtel-text {
    color: #ff0000;
    text-shadow: 0 0 20px rgba(255,0,0,0.5);
}

.slide-description {
    font-size: 1.3rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.slide-cta {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.cta-primary, .cta-secondary {
    padding: 1rem 2.5rem;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.cta-primary {
    background: var(--primary-color);
    color: white;
    box-shadow: 0 4px 15px rgba(39,174,96,0.4);
}

.cta-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(39,174,96,0.6);
}

.cta-secondary {
    background: rgba(255,255,255,0.2);
    color: white;
    backdrop-filter: blur(5px);
}

.cta-secondary:hover {
    background: rgba(255,255,255,0.3);
}

.payment-icons {
    display: flex;
    gap: 2rem;
    justify-content: center;
    margin-top: 2rem;
}

.payment-icon {
    width: 60px;
    height: 60px;
    filter: drop-shadow(0 4px 10px rgba(0,0,0,0.3));
}

.offer-badge {
    display: inline-block;
    padding: 0.5rem 1.5rem;
    background: linear-gradient(45deg, #f39c12, #e74c3c);
    color: white;
    border-radius: 30px;
    font-weight: bold;
    font-size: 1.2rem;
    margin-top: 2rem;
    animation: bounce 2s infinite;
}

/* Slideshow Controls */
.slideshow-controls {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    display: flex;
    justify-content: space-between;
    padding: 0 20px;
    z-index: 10;
}

.slide-control {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    backdrop-filter: blur(5px);
    transition: all 0.3s;
}

.slide-control:hover {
    background: rgba(255,255,255,0.5);
    transform: scale(1.1);
}

.slide-indicators {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 10;
}

.indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    cursor: pointer;
    transition: all 0.3s;
}

.indicator.active {
    background: white;
    transform: scale(1.3);
    box-shadow: 0 0 20px white;
}

/* Floating Promo */
.floating-promo {
    position: fixed;
    bottom: 20px;
    left: 20px;
    z-index: 1000;
    animation: slideInLeft 0.5s ease;
}

.promo-content {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
    border-radius: 15px;
    color: white;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    position: relative;
}

.close-promo {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 25px;
    height: 25px;
    border-radius: 50%;
    background: #e74c3c;
    color: white;
    border: none;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.promo-gif img {
    width: 60px;
    height: 60px;
}

.rotating {
    animation: rotate 10s linear infinite;
}

.promo-text h3 {
    font-size: 1.2rem;
    margin-bottom: 5px;
}

.promo-timer {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 10px 0;
}

.promo-btn {
    display: inline-block;
    padding: 8px 20px;
    background: white;
    color: #764ba2;
    text-decoration: none;
    border-radius: 25px;
    font-weight: bold;
    transition: all 0.3s;
}

.promo-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 5px 20px rgba(255,255,255,0.3);
}

/* Advertising Carousel */
.advertising-carousel {
    padding: 50px 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.carousel-container {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
    overflow: hidden;
}

.carousel-track {
    display: flex;
    transition: transform 0.5s ease;
    gap: 20px;
}

.ad-card {
    min-width: 300px;
    height: 250px;
    position: relative;
    border-radius: 15px;
    overflow: hidden;
    cursor: pointer;
    transform-style: preserve-3d;
    transition: transform 0.3s;
}

.ad-card:hover {
    transform: translateY(-10px) rotateX(5deg);
}

.ad-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.ad-card:hover img {
    transform: scale(1.1);
}

.ad-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    color: white;
    transform: translateY(100%);
    transition: transform 0.3s;
}

.ad-card:hover .ad-overlay {
    transform: translateY(0);
}

.ad-discount {
    display: inline-block;
    padding: 5px 15px;
    background: #e74c3c;
    border-radius: 20px;
    font-weight: bold;
    margin-top: 10px;
}

/* 3D Features */
.features-3d {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    padding: 80px 20px;
    max-width: 1200px;
    margin: 0 auto;
    perspective: 1000px;
}

.feature-3d-card {
    background: white;
    padding: 40px 20px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    transition: all 0.3s;
    transform-style: preserve-3d;
}

.feature-3d-card:hover {
    transform: translateZ(20px) rotateX(5deg);
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.feature-icon-3d {
    position: relative;
    width: 100px;
    height: 100px;
    margin: 0 auto 20px;
}

.feature-icon-3d img {
    width: 100%;
    height: 100%;
    position: relative;
    z-index: 2;
}

.icon-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle, var(--primary-color) 0%, transparent 70%);
    border-radius: 50%;
    filter: blur(20px);
    opacity: 0;
    transition: opacity 0.3s;
}

.feature-3d-card:hover .icon-glow {
    opacity: 0.5;
}

.float-3d {
    animation: float 3s ease-in-out infinite;
}

.feature-stats {
    margin-top: 15px;
    font-size: 0.9rem;
    color: var(--primary-color);
    font-weight: bold;
}

/* Special Offers */
.special-offers {
    padding: 80px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.section-title {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 50px;
    color: white;
}

.gradient-text {
    background: linear-gradient(45deg, #f3ec78, #af4261);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.offers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.offer-card {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 20px;
    color: white;
    position: relative;
    overflow: hidden;
    transition: all 0.3s;
}

.offer-card:hover {
    transform: scale(1.05);
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.offer-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    background: #e74c3c;
    padding: 8px 15px;
    border-radius: 25px;
    font-weight: bold;
    z-index: 2;
    animation: pulse 2s infinite;
}

.offer-image {
    height: 200px;
    overflow: hidden;
    border-radius: 15px;
    margin-bottom: 15px;
}

.offer-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.offer-card:hover .offer-img {
    transform: scale(1.1);
}

.offer-price {
    margin: 15px 0;
}

.old-price {
    text-decoration: line-through;
    opacity: 0.7;
    margin-right: 10px;
}

.new-price {
    font-size: 1.3rem;
    font-weight: bold;
    color: #f1c40f;
}

.offer-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(45deg, #f1c40f, #e67e22);
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.offer-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(241,196,15,0.4);
}

/* Category 3D Grid */
.category-showcase {
    padding: 80px 20px;
    background: #f8f9fa;
}

.category-3d-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
    perspective: 1000px;
}

.category-3d-item {
    height: 250px;
    cursor: pointer;
}

.category-3d-inner {
    position: relative;
    width: 100%;
    height: 100%;
    text-align: center;
    transition: transform 0.8s;
    transform-style: preserve-3d;
}

.category-3d-item:hover .category-3d-inner {
    transform: rotateY(180deg);
}

.category-3d-front, .category-3d-back {
    position: absolute;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
    border-radius: 15px;
    padding: 30px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.category-3d-front {
    background: white;
}

.category-3d-front img {
    width: 80px;
    height: 80px;
    margin-bottom: 15px;
}

.category-3d-back {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    transform: rotateY(180deg);
}

/* Products Grid */
.products-section {
    padding: 80px 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.filter-bar {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin: 30px 0;
    padding: 20px;
    background: rgba(255,255,255,0.8);
    border-radius: 50px;
}

.search-box {
    position: relative;
    flex: 1;
    max-width: 400px;
}

.search-box input {
    width: 100%;
    padding: 12px 20px 12px 45px;
    border: 2px solid #eee;
    border-radius: 30px;
    font-size: 1rem;
    transition: all 0.3s;
}

.search-box input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 20px rgba(39,174,96,0.2);
    outline: none;
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

.filter-select {
    padding: 12px 25px;
    border: 2px solid #eee;
    border-radius: 30px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
}

.filter-select:hover {
    border-color: var(--primary-color);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.product-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    transition: all 0.3s;
    position: relative;
}

.product-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.product-badges {
    position: absolute;
    top: 15px;
    left: 15px;
    z-index: 2;
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
    color: white;
}

.badge.discount {
    background: #e74c3c;
    animation: pulse 2s infinite;
}

.badge.featured {
    background: #f39c12;
}

.badge.low-stock {
    background: #e67e22;
}

.product-image {
    height: 250px;
    position: relative;
    overflow: hidden;
}

.product-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.product-card:hover .product-img {
    transform: scale(1.1);
}

.quick-view-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.product-card:hover .quick-view-overlay {
    opacity: 1;
}

.quick-view-btn {
    padding: 10px 25px;
    background: white;
    border: none;
    border-radius: 25px;
    font-weight: bold;
    cursor: pointer;
    transform: translateY(20px);
    transition: all 0.3s;
}

.product-card:hover .quick-view-btn {
    transform: translateY(0);
}

.quick-view-btn:hover {
    background: var(--primary-color);
    color: white;
}

.product-info {
    padding: 20px;
}

.product-name {
    font-size: 1.2rem;
    margin-bottom: 5px;
    color: #333;
}

.product-desc {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.product-rating {
    margin-bottom: 10px;
}

.star {
    color: #ddd;
    font-size: 1.1rem;
}

.star.filled {
    color: #f1c40f;
}

.rating-count {
    margin-left: 5px;
    color: #666;
    font-size: 0.8rem;
}

.product-price-section {
    margin: 15px 0;
}

.original-price {
    text-decoration: line-through;
    color: #999;
    margin-right: 10px;
}

.discounted-price {
    color: #e74c3c;
    font-weight: bold;
    font-size: 1.2rem;
}

.current-price {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--primary-color);
}

.stock-status {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 15px;
}

.stock-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.stock-indicator.in-stock {
    background: #27ae60;
    box-shadow: 0 0 10px #27ae60;
    animation: pulse 2s infinite;
}

.stock-indicator.low-stock {
    background: #e67e22;
    box-shadow: 0 0 10px #e67e22;
    animation: blink 1s infinite;
}

.quantity-wrapper {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 15px;
}

.qty-decrease, .qty-increase {
    width: 35px;
    height: 35px;
    border: 2px solid #eee;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.2rem;
    transition: all 0.3s;
}

.qty-decrease:hover, .qty-increase:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.qty-input {
    width: 60px;
    text-align: center;
    border: 2px solid #eee;
    border-radius: 8px;
    padding: 8px;
    font-size: 1rem;
}

.payment-options {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.payment-option {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px;
    border: 2px solid #eee;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.payment-option:hover {
    border-color: var(--primary-color);
}

.payment-option input[type="radio"] {
    display: none;
}

.payment-option input[type="radio"]:checked + img {
    transform: scale(1.1);
}

.payment-option img {
    width: 25px;
    height: 25px;
}

.buy-now-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: bold;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: all 0.3s;
}

.buy-now-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(39,174,96,0.4);
}

.btn-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shine 3s infinite;
}

.login-prompt {
    text-align: center;
}

.login-link {
    display: inline-block;
    padding: 10px 25px;
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
    text-decoration: none;
    border-radius: 25px;
    transition: all 0.3s;
}

.login-link:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(52,152,219,0.4);
}

/* Testimonials */
.testimonials {
    padding: 80px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.testimonial-carousel {
    max-width: 800px;
    margin: 0 auto;
    overflow: hidden;
}

.testimonial-track {
    display: flex;
    transition: transform 0.5s ease;
}

.testimonial-card {
    min-width: 100%;
    display: flex;
    gap: 30px;
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.testimonial-card img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary-color);
}

.testimonial-content p {
    font-size: 1.1rem;
    font-style: italic;
    margin-bottom: 15px;
    color: #555;
}

.testimonial-content h4 {
    color: #333;
    margin-bottom: 5px;
}

.testimonial-content .rating {
    color: #f1c40f;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.8);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 30px;
    border-radius: 20px;
    max-width: 800px;
    position: relative;
    animation: slideInDown 0.5s ease;
}

.close-modal {
    position: absolute;
    right: 20px;
    top: 10px;
    font-size: 30px;
    cursor: pointer;
    color: #999;
    transition: color 0.3s;
}

.close-modal:hover {
    color: #333;
}

/* Animations */
@keyframes slideInLeft {
    from {
        transform: translateX(-100px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideInRight {
    from {
        transform: translateX(100px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideInDown {
    from {
        transform: translateY(-100px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slideInUp {
    from {
        transform: translateY(100px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes float {
    0% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(5deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

@keyframes shine {
    0% { left: -100%; }
    20% { left: 100%; }
    100% { left: 100%; }
}

.animate-slide-up {
    animation: slideInUp 0.8s ease forwards;
    opacity: 0;
}

.animate-slide-down {
    animation: slideInDown 0.8s ease forwards;
    opacity: 0;
}

.animate-slide-left {
    animation: slideInLeft 0.8s ease forwards;
    opacity: 0;
}

.animate-slide-right {
    animation: slideInRight 0.8s ease forwards;
    opacity: 0;
}

.animate-bounce {
    animation: bounce 2s infinite;
}

.animate-pulse {
    animation: pulse 2s infinite;
}

/* Glass Effect */
.glass-effect {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

/* Responsive Design */
@media (max-width: 768px) {
    .slide-title {
        font-size: 2.5rem;
    }
    
    .filter-bar {
        flex-direction: column;
        border-radius: 20px;
    }
    
    .search-box {
        max-width: 100%;
    }
    
    .testimonial-card {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .floating-promo {
        left: 10px;
        right: 10px;
        max-width: calc(100% - 20px);
    }
    
    .promo-content {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
// Slideshow functionality
let currentSlide = 0;
const slides = document.querySelectorAll('.hero-slide');
const indicators = document.querySelectorAll('.indicator');
let slideInterval = setInterval(() => changeSlide(1), 5000);

function changeSlide(direction) {
    currentSlide += direction;
    if (currentSlide >= slides.length) currentSlide = 0;
    if (currentSlide < 0) currentSlide = slides.length - 1;
    updateSlide();
}

function goToSlide(index) {
    currentSlide = index;
    updateSlide();
}

function updateSlide() {
    slides.forEach(slide => slide.classList.remove('active'));
    indicators.forEach(ind => ind.classList.remove('active'));
    
    slides[currentSlide].classList.add('active');
    indicators[currentSlide].classList.add('active');
    
    // Reset interval
    clearInterval(slideInterval);
    slideInterval = setInterval(() => changeSlide(1), 5000);
}

// Carousel functionality
let carouselPosition = 0;
const carouselTrack = document.getElementById('adCarousel');
const cards = document.querySelectorAll('.ad-card');
const cardWidth = 320; // card width + gap

function moveCarousel(direction) {
    const maxPosition = cards.length - 3; // Show 3 cards at a time
    carouselPosition += direction;
    
    if (carouselPosition < 0) carouselPosition = 0;
    if (carouselPosition > maxPosition) carouselPosition = maxPosition;
    
    carouselTrack.style.transform = `translateX(-${carouselPosition * cardWidth}px)`;
    
    // Update dots
    updateCarouselDots();
}

function updateCarouselDots() {
    const dotsContainer = document.getElementById('carouselDots');
    dotsContainer.innerHTML = '';
    
    for (let i = 0; i < cards.length - 2; i++) {
        const dot = document.createElement('span');
        dot.className = `dot ${i === carouselPosition ? 'active' : ''}`;
        dot.onclick = () => {
            carouselPosition = i;
            carouselTrack.style.transform = `translateX(-${carouselPosition * cardWidth}px)`;
            updateCarouselDots();
        };
        dotsContainer.appendChild(dot);
    }
}

// Initialize carousel dots
updateCarouselDots();

// Search and filter functions
function searchMedicines() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const products = document.querySelectorAll('.product-card');
    
    products.forEach(product => {
        const name = product.dataset.name;
        if (name.includes(searchTerm)) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}

function filterMedicines() {
    const category = document.getElementById('categoryFilter').value;
    const products = document.querySelectorAll('.product-card');
    
    products.forEach(product => {
        if (!category || product.dataset.category === category) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}

function sortMedicines() {
    const sortBy = document.getElementById('sortFilter').value;
    const grid = document.getElementById('productsGrid');
    const products = Array.from(document.querySelectorAll('.product-card'));
    
    products.sort((a, b) => {
        switch(sortBy) {
            case 'price-low':
                return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            case 'price-high':
                return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
            case 'name':
                return a.dataset.name.localeCompare(b.dataset.name);
            case 'popular':
                return b.dataset.popularity - a.dataset.popularity;
            default:
                return 0;
        }
    });
    
    grid.innerHTML = '';
    products.forEach(product => grid.appendChild(product));
}

function filterByCategory(category) {
    document.getElementById('categoryFilter').value = category;
    filterMedicines();
    
    // Scroll to products
    document.getElementById('medicines').scrollIntoView({ behavior: 'smooth' });
}

// Quantity controls
function incrementQuantity(btn, max) {
    const input = btn.parentElement.querySelector('.qty-input');
    let value = parseInt(input.value) || 1;
    if (value < max) {
        input.value = value + 1;
    }
}

function decrementQuantity(btn) {
    const input = btn.parentElement.querySelector('.qty-input');
    let value = parseInt(input.value) || 1;
    if (value > 1) {
        input.value = value - 1;
    }
}

// Validate purchase
function validatePurchase(form) {
    const quantity = form.querySelector('.qty-input').value;
    const paymentMethod = form.querySelector('input[name="payment_method"]:checked');
    
    if (!paymentMethod) {
        alert('Please select a payment method');
        return false;
    }
    
    // Show animation
    document.getElementById('cartAnimation').style.display = 'block';
    setTimeout(() => {
        document.getElementById('cartAnimation').style.display = 'none';
    }, 2000);
    
    return true;
}

// Quick view
function showQuickView(id) {
    // Fetch product details via AJAX
    fetch(`get_medicine.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('quickViewContent').innerHTML = `
                <div class="quick-view-content">
                    <img src="${data.image}" alt="${data.name}">
                    <h2>${data.name}</h2>
                    <p>${data.description}</p>
                    <p class="price">UGX ${data.price_ugx}</p>
                    <p>Category: ${data.category}</p>
                    <p>Stock: ${data.stock}</p>
                    <button class="btn-primary" onclick="addToCart(${data.id})">Add to Cart</button>
                </div>
            `;
            document.getElementById('quickViewModal').style.display = 'block';
        });
}

function closeQuickView() {
    document.getElementById('quickViewModal').style.display = 'none';
}

// Add to cart
function addToCart(id) {
    // Add to cart logic here
    document.getElementById('cartAnimation').style.display = 'block';
    setTimeout(() => {
        document.getElementById('cartAnimation').style.display = 'none';
    }, 2000);
}

// Flash sale timer
function startFlashSale(minutes) {
    let time = minutes * 60;
    const timerElement = document.getElementById('flashSaleTimer');
    
    const countdown = setInterval(() => {
        const hours = Math.floor(time / 3600);
        const mins = Math.floor((time % 3600) / 60);
        const secs = time % 60;
        
        timerElement.innerHTML = `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        
        if (time <= 0) {
            clearInterval(countdown);
            timerElement.innerHTML = "Offer Expired";
        }
        time--;
    }, 1000);
}

// Start flash sale timer (2 hours)
startFlashSale(120);

// Close floating promo
function closePromo() {
    document.getElementById('floatingPromo').style.display = 'none';
}

// Parallax effect
window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    document.querySelectorAll('.parallax').forEach(el => {
        el.style.transform = `translateY(${scrolled * 0.5}px)`;
    });
});

// 3D tilt effect for feature cards
document.querySelectorAll('[data-tilt]').forEach(card => {
    card.addEventListener('mousemove', e => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        
        const rotateX = (y - centerY) / 10;
        const rotateY = (centerX - x) / 10;
        
        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(10px)`;
    });
    
    card.addEventListener('mouseleave', () => {
        card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0)';
    });
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('quickViewModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include 'footer.php'; ?>

<?php
// Helper function to get online medicine images
function getOnlineMedicineImage($medicineName) {
    // High-quality Unsplash images for medicines
    $imageMap = [
        'Paracetamol' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-1.2.1&auto=format&fit=crop&600x400',
        'Amoxicillin' => 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?ixlib=rb-1.2.1&auto=format&fit=crop&600x400',
        'Vitamin C' => 'https://images.unsplash.com/photo-1584017911766-451b3d0e8434?ixlib=rb-1.2.1&auto=format&fit=crop&600x400',
        'Ibuprofen' => 'https://images.unsplash.com/photo-1550574697-7d776f405ed2?ixlib=rb-1.2.1&auto=format&fit=crop&600x400',
        'Cough Syrup' => 'https://images.unsplash.com/photo-1628771065518-0d82f1938462?ixlib=rb-1.2.1&auto=format&fit=crop&600x400',
        'Antihistamine' => 'https://images.unsplash.com/photo-1631549916768-4119b2e5f926?ixlib=rb-1.2.1&auto=format&fit=crop&600x400'
    ];
    
    return isset($imageMap[$medicineName]) ? $imageMap[$medicineName] : 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-1.2.1&auto=format&fit=crop&600x400';
}
?>