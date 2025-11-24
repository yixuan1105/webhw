<?php 
// 避免重複引入，確保檔案只載入一次
if (!defined('FOOTER_INCLUDED')) {
    define('FOOTER_INCLUDED', true);
?>

<footer class="bg-dark text-white pt-5 pb-4 mt-5">
    <div class="container">
        <div class="row">
            
            <div class="col-lg-4 col-md-6 mb-4">
                <h5 class="text-uppercase fw-bold mb-3">🎓 成果管理平台</h5>
                <p class="text-white-50">
                    專為學術成果彙整、展示與系統化審核流程設計，幫助師生高效管理學習歷程。
                </p>
                <div class="mt-4">
                    <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white me-3"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="text-white"><i class="bi bi-instagram"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-4">
                <h5 class="text-uppercase fw-bold mb-3">快速存取</h5>
                <ul class="list-unstyled">
                    <li><a href="../index.php" class="text-white-50 text-decoration-none">首頁</a></li>
                    <li><a href="../login.php" class="text-white-50 text-decoration-none">登入</a></li>
                    <li><a href="../profile.php" class="text-white-50 text-decoration-none">個人檔案</a></li>
                    </ul>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="text-uppercase fw-bold mb-3">系統資源</h5>
                <ul class="list-unstyled">
                    <li><a href="upload_achievement.php" class="text-white-50 text-decoration-none">成果上傳</a></li>
                    <li><a href="terms.php" class="text-white-50 text-decoration-none">服務條款</a></li>
                    <li><a href="privacy.php" class="text-white-50 text-decoration-none">隱私政策</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="text-uppercase fw-bold mb-3">聯繫我們</h5>
                <p class="text-white-50">
                    <i class="bi bi-geo-alt-fill me-2"></i> 新北市新莊區科技路 1 號
                </p>
                <p class="text-white-50">
                    <i class="bi bi-envelope-fill me-2"></i> info@project.edu.tw
                </p>
                <p class="text-white-50">
                    <i class="bi bi-telephone-fill me-2"></i> +886-2-1234-5678
                </p>
            </div>

        </div>
    </div>
    
    <div class="text-center p-3 bg-dark" style="border-top: 1px solid rgba(255, 255, 255, 0.1);">
        <small class="text-white-50">
            &copy; <?= date('Y') ?> 專題製作小組. All Rights Reserved.
        </small>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>

</body>
</html>

<?php 
// 結束 if (!defined('FOOTER_INCLUDED')) 的判斷
} 
?>