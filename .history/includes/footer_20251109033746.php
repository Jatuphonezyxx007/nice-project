<footer class="bg-black text-light pt-5 pb-4 mt-5 border-top border-secondary-subtle">
    <div class="container">
        <div class="row gy-4">

            <div class="col-md-4">
                <h5 class="text-uppercase fw-bold mb-3">MyPOS System</h5>
                <p class="text-muted small">
                    ระบบจัดการสินค้าครบวงจร ที่ช่วยให้การขายและการจัดการสต็อกของคุณง่ายขึ้น
                </p>
            </div>

            <div class="col-md-4">
                <h6 class="text-uppercase fw-bold mb-3">ลิงก์ด่วน</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="footer-link">หน้าแรก</a></li>
                    <li><a href="#" class="footer-link">สินค้า</a></li>
                    <li><a href="#" class="footer-link">รายงาน</a></li>
                    <li><a href="#" class="footer-link">ติดต่อเรา</a></li>
                </ul>
            </div>

            <div class="col-md-4">
                <h6 class="text-uppercase fw-bold mb-3">ติดต่อเรา</h6>
                <ul class="list-unstyled text-muted small">
                    <li><i class="bi bi-geo-alt-fill text-primary me-2"></i> กรุงเทพฯ, ประเทศไทย</li>
                    <li><i class="bi bi-telephone-fill text-primary me-2"></i> 081-234-5678</li>
                    <li><i class="bi bi-envelope-fill text-primary me-2"></i> support@mypos.com</li>
                </ul>
            </div>
        </div>

        <hr class="opacity-25 my-4">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">

            <div class="text-muted small mb-3 mb-md-0">
                © <?php echo date("Y"); ?> MyPOS System — All Rights Reserved.
            </div>

            <div>
                <a href="#" class="btn btn-outline-light btn-floating m-1 rounded-circle" role="button">
                    <i class="bi bi-facebook"></i>
                </a>
                <a href="#" class="btn btn-outline-light btn-floating m-1 rounded-circle" role="button">
                    <i class="bi bi-instagram"></i>
                </a>
                <a href="#" class="btn btn-outline-light btn-floating m-1 rounded-circle" role="button">
                    <i class="bi bi-twitter-x"></i>
                </a>
                <a href="#" class="btn btn-outline-light btn-floating m-1 rounded-circle" role="button">
                    <i class="bi bi-github"></i>
                </a>
            </div>
        </div>
    </div>
</footer>

<style>
    /* เอฟเฟกต์สำหรับ Quick Links */
    .footer-link {
        color: var(--bs-secondary-color);
        /* ใช้สี muted ของ Bootstrap */
        text-decoration: none;
        transition: all 0.2s ease-in-out;
    }

    .footer-link:hover {
        color: var(--bs-light);
        /* เปลี่ยนเป็นสีขาวเมื่อเมาส์ชี้ */
        padding-left: 5px;
        /* ขยับเล็กน้อย */
    }

    /* สไตล์สำหรับปุ่ม Social (เพื่อให้เป็นวงกลมขนาดเท่ากัน) */
    .btn-floating {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
</style>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"></script>

</body>

</html>