<style>
    .footer-link-list a {
        transition: all 0.2s ease-in-out;
        color: rgba(255, 255, 255, 0.55);
        /* สีเทาอ่อน (text-white-50) */
    }

    .footer-link-list a:hover {
        color: #FFFFFF;
        /* สีขาวเมื่อ hover */
        padding-left: 5px;
        /* ขยับเล็กน้อย */
    }

    .footer-social-icon {
        transition: all 0.2s ease-in-out;
        color: #FFFFFF;
    }

    .footer-social-icon:hover {
        opacity: 0.7;
        transform: translateY(-2px);
        /* ขยับขึ้นเล็กน้อย */
    }
</style>

<footer class="bg-dark text-light pt-5 pb-4 mt-5 border-top border-4 border-primary">
    <div class="container">
        <div class="row gy-4">

            <div class="col-lg-4 col-md-6">
                <h5 class="text-uppercase fw-bold mb-3 d-flex align-items-center">
                    <i class="bi bi-bar-chart-steps fs-3 me-2"></i>
                    TheStep
                </h5>
                <p class="text-white-50 small">
                    ผู้นำด้านนวัตกรรมและเทคโนโลยีครบวงจร มุ่งมั่นพัฒนาโซลูชันเพื่อขับเคลื่อนธุรกิจของคุณ
                </p>
                <div>
                    <a href="#" class="footer-social-icon me-3"><i class="bi bi-facebook fs-5"></i></a>
                    <a href="#" class="footer-social-icon me-3"><i class="bi bi-instagram fs-5"></i></a>
                    <a href="#" class="footer-social-icon me-3"><i class="bi bi-twitter-x fs-5"></i></a>
                    <a href="#" class="footer-social-icon"><i class="bi bi-line fs-5"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h6 class="text-uppercase fw-bold text-white-50 mb-3">เมนูหลัก</h6>
                <ul class="list-unstyled footer-link-list">
                    <li class="mb-2"><a href="#" class="text-decoration-none">เกี่ยวกับเรา</a></li>
                    <li class="mb-2"><a href="#" class="text-decoration-none">บริการของเรา</a></li>
                    <li class="mb-2"><a href="#" class="text-decoration-none">ร่วมงานกับเรา</a></li>
                    <li class="mb-2"><a href="#" class="text-decoration-none">ติดต่อเรา</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-6">
                <h6 class="text-uppercase fw-bold text-white-50 mb-3">ช่วยเหลือ</h6>
                <ul class="list-unstyled footer-link-list">
                    <li class="mb-2"><a href="#" class="text-decoration-none">คำถามที่พบบ่อย</a></li>
                    <li class="mb-2"><a href="#" class="text-decoration-none">นโยบายความเป็นส่วนตัว</a></li>
                    <li class="mb-2"><a href="#" class="text-decoration-none">เงื่อนไขการใช้งาน</a></li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-6">
                <h6 class="text-uppercase fw-bold text-white-50 mb-3">ติดต่อเรา</h6>
                <ul class="list-unstyled text-white-50 small">
                    <li class="d-flex mb-2">
                        <i class="bi bi-building-fill text-primary me-3 fs-5"></i>
                        <span>อาคาร TheStep Tower, 99 ถ.สาทรเหนือ<br>กรุงเทพมหานคร 10500</span>
                    </li>
                    <li class="d-flex mb-2">
                        <i class="bi bi-telephone-fill text-primary me-3 fs-5"></i>
                        <span>02-123-4567 (ฝ่ายบริการลูกค้า)</span>
                    </li>
                    <li class="d-flex mb-2">
                        <i class="bi bi-envelope-fill text-primary me-3 fs-5"></i>
                        <span>info@thestep.co.th</span>
                    </li>
                    <li class="d-flex mb-2">
                        <i class="bi bi-file-earmark-text-fill text-primary me-3 fs-5"></i>
                        <span>เลขประจำตัวผู้เสียภาษี: 01075XXXXXXX</span>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="border-secondary my-4">
        <div class="text-center text-white-50 small">
            © <?php echo date("Y"); ?> TheStep Public Company Limited | บริษัท เดอะสเต็ป จำกัด (มหาชน).
            <br>
            All Rights Reserved. สงวนลิขสิทธิ์.
        </div>
    </div>
</footer>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>

</body>

</html>