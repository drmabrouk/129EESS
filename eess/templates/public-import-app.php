<?php
/**
 * Template Name: Dedicated Student Import & Export Portal
 * Shortcode: [import]
 */

if (!defined('ABSPATH')) exit;

$school_info = SM_Settings::get_school_info();
$sys_logo = !empty($school_info['school_logo']) ? $school_info['school_logo'] : (!empty($school_info['logo_url']) ? $school_info['logo_url'] : SM_PLUGIN_URL . 'assets/images/logo.png');
$school_name = 'مؤسسة الشعلة للتعليم والتطوير';
$ajax_url = admin_url('admin-ajax.php');
$admin_nonce = wp_create_nonce('sm_admin_action');

$can_import = current_user_can('manage_options') || current_user_can('إدارة_الطلاب') || in_array('sm_system_admin', (array)wp_get_current_user()->roles);
?>

<div class="eess-import-app" style="max-width: 820px; margin: 20px auto; background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(15,23,42,0.08); font-family: 'Cairo', sans-serif; direction: rtl; padding: 28px; box-sizing: border-box; color: #0f172a;">

    <!-- Branding Header -->
    <div style="text-align: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 24px;">
        <div style="width: 72px; height: 72px; margin: 0 auto 10px auto; background: #ffffff; border-radius: 16px; padding: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.06); border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center;">
            <img src="<?php echo esc_url($sys_logo); ?>" style="width: 100%; height: 100%; object-fit: contain; border-radius: 12px;" alt="Logo">
        </div>
        <h2 style="margin: 0 0 4px 0; font-size: 22px; font-weight: 900; color: #0f172a;"><?php echo esc_html($school_name); ?></h2>
        <div style="font-size: 14px; color: #881337; font-weight: 800;">البوابة الموحدة لتصدير واستيراد بيانات الطلاب الشاملة (Excel / CSV)</div>
    </div>

    <?php if (!$can_import): ?>
    <div style="background: #fef2f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 20px; text-align: center; color: #991b1b; font-weight: 800; font-size: 14px;">
        عذراً، يتطلب الوصول لهذه البوابة صلاحيات إدارة الطلاب أو مدير النظام.
    </div>
    <?php else: ?>

    <!-- Control Actions Banner (Import Template & Export Database) -->
    <div style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
        <a href="<?php echo admin_url('admin-ajax.php?action=sm_export_students_csv&nonce=' . $admin_nonce); ?>" style="flex: 1; height: 42px; background: #881337; color: white; border-radius: 10px; font-weight: 800; font-size: 13px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(136,19,55,0.18);">
            <span class="dashicons dashicons-download" style="font-size: 18px;"></span>
            <span>تصدير سجلاّت الطلاب المعتمدة (Excel/CSV)</span>
        </a>
        <a href="<?php echo admin_url('admin-ajax.php?action=sm_download_student_import_template'); ?>" target="_blank" style="flex: 1; height: 42px; background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; border-radius: 10px; font-weight: 800; font-size: 13px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <span class="dashicons dashicons-media-document" style="font-size: 18px; color: #16a34a;"></span>
            <span>تحميل نموذج الاستيراد الشامل (16 عمود)</span>
        </a>
    </div>

    <!-- STEP 1: FILE SELECTION & UPLOAD -->
    <div id="imp-area-selection">
        <div style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 16px; padding: 30px 20px; text-align: center; margin-bottom: 20px; transition: border-color 0.2s;" ondragover="this.style.borderColor='#881337'" ondragleave="this.style.borderColor='#cbd5e1'">
            <div style="width: 56px; height: 56px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; color: #881337;">
                <span class="dashicons dashicons-upload" style="font-size: 28px; width: 28px; height: 28px;"></span>
            </div>
            <h3 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 800; color: #0f172a;">اختر أو أسقط ملف البيانات الشامل (Excel / CSV)</h3>
            <p style="margin: 0 0 16px 0; font-size: 12.5px; color: #64748b; font-weight: 600;">يدعم النظام معالجة الملفات الضخمة حتى 2,000+ سجل بآلية المهام الخلفية المستمرة</p>

            <input type="file" id="imp_file_input" accept=".csv, .xlsx, .xls" style="display: none;" onchange="impFileSelected(this)">
            <button type="button" onclick="document.getElementById('imp_file_input').click()" style="height: 42px; padding: 0 24px; background: #0f172a; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: pointer;">
                📁 استعراض واختيار الملف
            </button>
            <div id="imp-file-name-preview" style="margin-top: 10px; font-size: 12.5px; font-weight: 800; color: #15803d; display: none;"></div>
        </div>

        <!-- System Requirements Summary Box -->
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 16px; font-size: 12px; color: #166534; line-height: 1.6; margin-bottom: 20px;">
            <strong>📋 ضوابط واستحقاقات الملف الشامل:</strong><br>
            • الحقول الإجبارية: اسم الطالب الكامل، رمز/اسم المدرسة، الصف الدراسي، والشعبة.<br>
            • الحقول الاختيارية: الهوية الوطنية/الإقامة (يمكن تركها فارغة ولن تسبب تكرار أو تعارض مفاتيح).<br>
            • يتم ربط الطالب تلقائياً بقسم شؤون الطلاب (كود 3) وتوليد كود الطالب الرقمي والرقم التسلسلي تلقائياً.
        </div>

        <div style="display: flex; justify-content: flex-end;">
            <button type="button" id="imp_btn_start" disabled onclick="impStartUpload()" style="height: 44px; padding: 0 28px; background: #881337; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13.5px; cursor: not-allowed; opacity: 0.5;">
                بدء رفع وتحليل الملف ➔
            </button>
        </div>
    </div>

    <!-- STEP 2: REAL-TIME PROGRESS & STATUS INTERFACE -->
    <div id="imp-area-progress" style="display: none;">
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span id="imp-status-text" style="font-size: 14px; font-weight: 800; color: #0f172a;">جاري متابعة مهمة الاستيراد خلفياً... ⏳</span>
                <span id="imp-percentage" style="font-size: 16px; font-weight: 900; color: #881337;">0%</span>
            </div>

            <div style="width: 100%; height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden; margin-bottom: 16px;">
                <div id="imp-progress-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #881337, #be123c); transition: width 0.3s ease;"></div>
            </div>

            <!-- Detailed Stats Grid -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; text-align: center;">
                <div style="background: white; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 700;">إجمالي السجلات</div>
                    <div style="font-size: 16px; font-weight: 900; color: #0f172a;" id="imp-stat-total">0</div>
                </div>
                <div style="background: white; border: 1px solid #86efac; border-radius: 10px; padding: 10px;">
                    <div style="font-size: 11px; color: #166534; font-weight: 700;">المستوردة بنجاح</div>
                    <div style="font-size: 16px; font-weight: 900; color: #16a34a;" id="imp-stat-success">0</div>
                </div>
                <div style="background: white; border: 1px solid #fef08a; border-radius: 10px; padding: 10px;">
                    <div style="font-size: 11px; color: #854d0e; font-weight: 700;">المحدثة / المكررة</div>
                    <div style="font-size: 16px; font-weight: 900; color: #ca8a04;" id="imp-stat-dup">0</div>
                </div>
                <div style="background: white; border: 1px solid #fecdd3; border-radius: 10px; padding: 10px;">
                    <div style="font-size: 11px; color: #991b1b; font-weight: 700;">المرفوضة / الأخطاء</div>
                    <div style="font-size: 16px; font-weight: 900; color: #dc2626;" id="imp-stat-error">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FINAL COMPLETION SUMMARY SCREEN -->
    <div id="imp-area-summary" style="display: none; text-align: center; padding: 10px 0;">
        <div style="width: 64px; height: 64px; background: #dcfce7; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #16a34a; margin-bottom: 14px;">
            <span class="dashicons dashicons-yes" style="font-size: 36px; width: 36px; height: 36px;"></span>
        </div>
        <h3 style="margin: 0 0 6px 0; font-size: 20px; font-weight: 900; color: #15803d;">تم اكتمال عملية استيراد البيانات بنجاح</h3>
        <p style="font-size: 13px; color: #475569; margin: 0 0 20px 0;">أصبحت كافة السجلات المستوردة متاحة الآن مباشرة في القوائم الرسمية لشؤون الطلاب.</p>

        <div id="imp-summary-details-box" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 14px; padding: 16px; text-align: right; font-size: 12px; color: #334155; line-height: 1.6; margin-bottom: 20px; max-height: 200px; overflow-y: auto;"></div>

        <div style="display: flex; justify-content: center; gap: 12px;">
            <button type="button" onclick="location.reload()" style="height: 42px; padding: 0 24px; background: #0f172a; color: white; border: none; border-radius: 10px; font-weight: 800; font-size: 13px; cursor: pointer;">استيراد ملف جديد ↺</button>
        </div>
    </div>

    <?php endif; ?>

</div>

<script>
let impSelectedFile = null;
let impActiveFilePath = null;
let impIsProcessing = false;

document.addEventListener('DOMContentLoaded', function() {
    impCheckActiveJobState();
});

function impCheckActiveJobState() {
    jQuery.post('<?php echo $ajax_url; ?>', {
        action: 'eess_get_import_job_status'
    }, function(res) {
        if (res.success && res.data && res.data.active && res.data.job) {
            const job = res.data.job;
            if (job.status === 'running') {
                impActiveFilePath = job.file_path;
                document.getElementById('imp-area-selection').style.display = 'none';
                document.getElementById('imp-area-progress').style.display = 'block';
                impUpdateUIFromJob(job);
                impProcessChunk(job.file_path, job.offset || job.processed || 0, 0);
            } else if (job.status === 'completed') {
                document.getElementById('imp-area-selection').style.display = 'none';
                impShowCompletedSummary(job);
            }
        }
    });
}

function impFileSelected(input) {
    if (input.files && input.files[0]) {
        impSelectedFile = input.files[0];
        document.getElementById('imp-file-name-preview').innerText = '✓ الملف المختار: ' + impSelectedFile.name + ' (' + (impSelectedFile.size / 1024).toFixed(1) + ' كيلوبايت)';
        document.getElementById('imp-file-name-preview').style.display = 'block';

        const btn = document.getElementById('imp_btn_start');
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }
}

function impStartUpload() {
    if (!impSelectedFile) return;

    document.getElementById('imp-area-selection').style.display = 'none';
    document.getElementById('imp-area-progress').style.display = 'block';

    const formData = new FormData();
    formData.append('action', 'sm_process_import_chunk');
    formData.append('csv_file', impSelectedFile);
    formData.append('nonce', '<?php echo $admin_nonce; ?>');

    fetch('<?php echo $ajax_url; ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data && res.data.file_path) {
            impActiveFilePath = res.data.file_path;
            if (res.data.job) impUpdateUIFromJob(res.data.job);
            impProcessChunk(res.data.file_path, 0, 0);
        } else {
            alert('فشل رفع وتحليل الملف: ' + (res.data || 'خطأ غير معروف'));
            location.reload();
        }
    }).catch(err => {
        alert('حدث خطأ في الاتصال بالسيرفر أثناء رفع الملف.');
        location.reload();
    });
}

function impUpdateUIFromJob(job) {
    const totalRows = job.total || 1;
    const processed = job.processed || 0;
    const pct = Math.min(100, Math.round((processed / totalRows) * 100));

    document.getElementById('imp-stat-total').innerText = totalRows;
    document.getElementById('imp-stat-success').innerText = job.success || 0;
    document.getElementById('imp-stat-dup').innerText = job.duplicate || 0;
    document.getElementById('imp-stat-error').innerText = job.error || 0;

    document.getElementById('imp-percentage').innerText = pct + '%';
    document.getElementById('imp-progress-bar').style.width = pct + '%';
    document.getElementById('imp-status-text').innerText = `جاري المعالجة الخلفية... تم إنجاز ${processed} من ${totalRows} طالب (${pct}%)`;
}

function impProcessChunk(filePath, offset, retryCount) {
    if (impIsProcessing) return;
    impIsProcessing = true;

    const formData = new FormData();
    formData.append('action', 'sm_process_import_chunk');
    formData.append('file_path', filePath);
    formData.append('offset', offset);
    formData.append('nonce', '<?php echo $admin_nonce; ?>');

    fetch('<?php echo $ajax_url; ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        impIsProcessing = false;
        if (res.success) {
            const finished = res.data.finished;
            const job = res.data.job || res.data.results || {};
            impUpdateUIFromJob(job);

            if (finished) {
                document.getElementById('imp-area-progress').style.display = 'none';
                impShowCompletedSummary(job);
            } else {
                setTimeout(() => impProcessChunk(filePath, res.data.total_so_far, 0), 100);
            }
        } else {
            if (retryCount < 3) {
                document.getElementById('imp-status-text').innerText = `خطأ مؤقت بالشبكة. إعادة محاولة الاتصال (${retryCount + 1}/3)...`;
                setTimeout(() => impProcessChunk(filePath, offset, retryCount + 1), 2500);
            } else {
                document.getElementById('imp-status-text').innerText = `تعذر استكمال الاتصال. جاري إعادة الربط بآخر موضع مسجل بالسيرفر...`;
                setTimeout(() => impCheckActiveJobState(), 3000);
            }
        }
    }).catch(err => {
        impIsProcessing = false;
        if (retryCount < 3) {
            document.getElementById('imp-status-text').innerText = `انقطاع مؤقت بالاتصال. جاري التوصيل التلقائي (${retryCount + 1}/3)...`;
            setTimeout(() => impProcessChunk(filePath, offset, retryCount + 1), 3000);
        } else {
            document.getElementById('imp-status-text').innerText = `جاري استعادة حالة مهمة الاستيراد المسجلة بالسيرفر...`;
            setTimeout(() => impCheckActiveJobState(), 4000);
        }
    });
}

function impShowCompletedSummary(job) {
    let html = `<strong>خلاصة نتائج استيراد الملف الشامل:</strong><br>`;
    html += `• إجمالي السجلات المعالجة: ${job.processed || job.total}<br>`;
    html += `• السجلات الجديدة المستوردة بنجاح: ${job.success || 0}<br>`;
    html += `• السجلات المحدثة/المكررة: ${job.duplicate || 0}<br>`;
    html += `• السجلات المرفوضة: ${job.error || 0}<br>`;

    if (job.details && job.details.length > 0) {
        html += `<br><strong>تفاصيل الملاحظات والأخطاء:</strong><br>`;
        job.details.forEach(d => {
            html += `<div style="color:${d.type==='error'?'#dc2626':'#0284c7'}">• ${d.msg}</div>`;
        });
    }
    document.getElementById('imp-summary-details-box').innerHTML = html;
    document.getElementById('imp-area-summary').style.display = 'block';
}
</script>
