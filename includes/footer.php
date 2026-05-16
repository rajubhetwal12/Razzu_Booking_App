<?php defined('BASE_URL')||require_once __DIR__.'/../config/db.php';?>
</div><!-- /.page -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="<?=BASE_URL?>/" class="logo"><div class="logo-icon" style="width:32px;height:32px;font-size:.85rem">🏨</div><span class="logo-text" style="font-size:1.1rem">LuxStay</span></a>
        <p>Nepal's premier luxury hotel booking platform. Discover extraordinary stays across the Himalayas.</p>
        <div class="socials">
          <a href="#" class="soc-btn">f</a><a href="#" class="soc-btn">📷</a>
          <a href="#" class="soc-btn">𝕏</a><a href="#" class="soc-btn">in</a>
        </div>
      </div>
      <div>
        <p class="footer-h">Explore</p>
        <ul class="footer-links">
          <li><a href="<?=BASE_URL?>/">Home</a></li>
          <li><a href="<?=BASE_URL?>/hotels.php">All Hotels</a></li>
          <li><a href="<?=BASE_URL?>/hotels.php?featured=1">Featured</a></li>
          <li><a href="<?=BASE_URL?>/hotels.php?sort=price_asc">Best Deals</a></li>
        </ul>
      </div>
      <div>
        <p class="footer-h">Account</p>
        <ul class="footer-links">
          <li><a href="<?=BASE_URL?>/register.php">Sign Up Free</a></li>
          <li><a href="<?=BASE_URL?>/login.php">Sign In</a></li>
          <li><a href="<?=BASE_URL?>/dashboard/customer.php">My Bookings</a></li>
          <li><a href="<?=BASE_URL?>/forgot-password.php">Reset Password</a></li>
        </ul>
      </div>
      <div>
        <p class="footer-h">Contact</p>
        <ul class="footer-links">
          <li>📍 Thamel, Kathmandu, Nepal</li>
          <li><a href="tel:+9771400000">📞 +977-1-400000</a></li>
          <li><a href="mailto:info@luxstay.com">✉️ info@luxstay.com</a></li>
          <li>🕐 24/7 Support</li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">© <?=date('Y')?> LuxStay. All rights reserved. Made with ❤️ in Nepal 🇳🇵</div>
  </div>
</footer>
<script src="<?=BASE_URL?>/assets/js/main.js"></script>
</body></html>
