<?php
// privacy.php - 隱私政策頁面
// ... (PHP 邏輯保持不變) ...
require_once('header.php'); 
?>

<div class="container" style="padding: 40px 20px;">
    
    <div class="card">
        <h1>學生學習成果認證系統隱私權政策</h1>
        <hr>

        <p>「學生學習成果認證系統」深知個人資料的重要性，特此依據中華民國《個人資料保護法》及相關法令，訂定本隱私權政策。</p>

        <h2 style="margin-top: 30px;">一、個人資料的蒐集與類別</h2>
        <p>1.1 蒐集方式：本系統透過註冊程序、成果上傳及服務使用過程，蒐集您的個人資料。</p>
        <p>1.2 蒐集類別：我們主要蒐集以下類別之資料：</p>
        <ul style="margin-left: 20px; list-style-type: circle;">
            <li>識別類：姓名、學號、電子郵件地址。</li>
            <li>特徵類：職業（如學生）、職務（如管理員）。</li>
            <li>行為與成果類：上傳之成果內容、認證狀態、系統使用紀錄（包括 IP 位址、瀏覽器類型及時間戳記）。</li>
        </ul>
        <div class="alert alert-error" style="margin-top: 15px;">
            <p><strong>注意：</strong>我們將嚴格遵守《個人資料保護法》之規範，未經您的書面同意，絕不將資料提供給無關之第三方。</p>
        </div>

        <h2 style="margin-top: 30px;">二、個人資料的處理與利用</h2>
        <p>2.1 利用期間：您的個人資料將於本系統服務存續期間內予以保留。</p>
        <p>2.3 利用目的：您的個人資料將嚴格用於以下目的：</p>
        <ul style="margin-left: 20px; list-style-type: square;">
            <li><strong>身分識別與帳號管理；</strong></li>
            <li><strong>成果審核與認證流程；</strong></li>
            <li>教學品質分析；</li>
            <li>提供系統支援與安全防護。</li>
        </ul>

        <h2 style="margin-top: 30px; color: #27ae60;">三、資料安全與當事人權利</h2>
        <p>3.1 安全維護： 本系統採取了符合業界標準的安全措施，包括但不限於加密技術與存取控制，以保護您的個人資料。</p>
        <p>3.2 當事人權利： 依據《個人資料保護法》，您對自己的個人資料享有下列六項權利：</p>
        <ol style="margin-left: 20px;">
            <li>查詢及請求閱覽。</li>
            <li>請求製給複製本。</li>
            <li>請求補充或更正。</li>
            <li>請求停止蒐集、處理、利用。</li>
            <li>請求刪除。</li>
            <li>行使權利的方式請透過系統公告之聯繫方式進行。</li>
        </ol>

        <h2 style="margin-top: 30px;">四、政策更新與生效</h2>
        <p>本系統保留隨時修訂本隱私權政策的權利。建議您定期查閱本政策，修訂後的條款將自公佈日起生效。</p>
        
        <p style="margin-top: 40px; font-style: italic; color: #777;">
            本隱私權政策於<?php echo date('Y 年 m 月 d 日'); ?> 首次發佈並生效。
        </p>
    </div>

</div>

<?php
// ... (PHP 邏輯保持不變) ...
require_once('footer.php'); 
?>