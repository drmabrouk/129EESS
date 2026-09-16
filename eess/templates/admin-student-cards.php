<?php
/**
 * Dedicated EESS Student Cards Management Module Template
 * Section: بطاقات الطلاب
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

$current_user_id = get_current_user_id();
$is_sys_admin = current_user_can('manage_options') || in_array('sm_system_admin', (array)wp_get_current_user()->roles);

// Institution Scope Isolation
$user_scope = EESS_Org_Helper::get_user_scope($current_user_id);
$inst_id = $user_scope['institution_id'] ?? null;

// Handle Status Change Action
if (isset($_POST['sm_update_card_status_nonce']) && wp_verify_nonce($_POST['sm_update_card_status_nonce'], 'sm_update_card_status_action')) {
    $req_id = intval($_POST['request_id'] ?? 0);
    $new_status = sanitize_text_field($_POST['new_status'] ?? '');
    $admin_notes = sanitize_textarea_field($_POST['admin_notes'] ?? '');

    if ($req_id && !empty($new_status)) {
        $update_data = array(
            'status' => $new_status,
            'updated_at' => current_time('mysql')
        );
        if (!empty($admin_notes)) {
            $update_data['admin_notes'] = $admin_notes;
        }
        $wpdb->update("{$wpdb->prefix}sm_exit_card_requests", $update_data, array('id' => $req_id));
        echo '<div style="background:#dcfce7; border:1px solid #86efac; color:#166534; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-weight:800; font-size:13px;">✓ تم تحديث حالة طلب تصريح الخروج بنجاح.</div>';
    }
}

// Search & Filter Query
$search_q = sanitize_text_field($_GET['card_search'] ?? '');
$status_f = sanitize_text_field($_GET['status_filter'] ?? '');
$school_f = sanitize_text_field($_GET['school_filter'] ?? '');

$where_clauses = array("1=1");
$where_args = array();

if (!empty($search_q)) {
    $where_clauses[] = "(s.name LIKE %s OR s.student_code LIKE %s OR s.national_id LIKE %s OR r.reference_no LIKE %s OR r.parent_phone LIKE %s)";
    $like = '%' . $wpdb->esc_like($search_q) . '%';
    $where_args[] = $like; $where_args[] = $like; $where_args[] = $like; $where_args[] = $like; $where_args[] = $like;
}

if (!empty($status_f)) {
    $where_clauses[] = "r.status = %s";
    $where_args[] = $status_f;
}

if (!$is_sys_admin && $inst_id) {
    $where_clauses[] = "s.school_id = %d";
    $where_args[] = $inst_id;
} elseif (!empty($school_f)) {
    $where_clauses[] = "s.school_id = %d";
    $where_args[] = intval($school_f);
}

$where_sql = implode(' AND ', $where_clauses);

// Fetch Summary Stats
$stats_sql = "SELECT r.status, COUNT(*) as cnt FROM {$wpdb->prefix}sm_exit_card_requests r JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id WHERE " . ($is_sys_admin ? "1=1" : "s.school_id = " . intval($inst_id)) . " GROUP BY r.status";
$raw_stats = $wpdb->get_results($stats_sql);
$stats = array('submitted' => 0, 'under_review' => 0, 'parent_confirmation' => 0, 'approved' => 0, 'preparing' => 0, 'issued' => 0, 'rejected' => 0, 'total' => 0);
foreach ($raw_stats as $st) {
    $stats[$st->status] = (int)$st->cnt;
    $stats['total'] += (int)$st->cnt;
}

// Fetch Paginated Requests List
$sql = "SELECT r.*, s.name as student_name, s.student_code, s.class_name, s.section, s.photo_url, s.national_id, s.school_id FROM {$wpdb->prefix}sm_exit_card_requests r JOIN {$wpdb->prefix}sm_students s ON r.student_id = s.id WHERE {$where_sql} ORDER BY r.created_at DESC LIMIT 100";
$requests = !empty($where_args) ? $wpdb->get_results($wpdb->prepare($sql, $where_args)) : $wpdb->get_results($sql);

$institutions = EESS_Org_Helper::get_institutions();
?>

<div class="eess-student-cards-app" style="font-family: 'Cairo', sans-serif; direction: rtl; color: #0f172a;">

    <!-- Top Summary Statistics Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-bottom: 20px;">
        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 14px; text-align: center;">
            <div style="font-size: 11.5px; color: #64748b; font-weight: 700;">إجمالي الطلبات</div>
            <div style="font-size: 20px; font-weight: 900; color: #0f172a;"><?php echo $stats['total']; ?></div>
        </div>
        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 14px; text-align: center;">
            <div style="font-size: 11.5px; color: #166534; font-weight: 700;">طلبات جديدة</div>
            <div style="font-size: 20px; font-weight: 900; color: #16a34a;"><?php echo $stats['submitted']; ?></div>
        </div>
        <div style="background: #fefce8; border: 1px solid #fef08a; border-radius: 12px; padding: 14px; text-align: center;">
            <div style="font-size: 11.5px; color: #854d0e; font-weight: 700;">قيد المراجعة</div>
            <div style="font-size: 20px; font-weight: 900; color: #ca8a04;"><?php echo $stats['under_review']; ?></div>
        </div>
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px; text-align: center;">
            <div style="font-size: 11.5px; color: #1e40af; font-weight: 700;">معتمدة ومقبولة</div>
            <div style="font-size: 20px; font-weight: 900; color: #2563eb;"><?php echo $stats['approved']; ?></div>
        </div>
        <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 12px; padding: 14px; text-align: center;">
            <div style="font-size: 11.5px; color: #6b21a8; font-weight: 700;">جاهزة / قيد الطباعة</div>
            <div style="font-size: 20px; font-weight: 900; color: #9333ea;"><?php echo $stats['preparing']; ?></div>
        </div>
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 14px; text-align: center;">
            <div style="font-size: 11.5px; color: #065f46; font-weight: 700;">بطاقات مفعّلة</div>
            <div style="font-size: 20px; font-weight: 900; color: #059669;"><?php echo $stats['issued']; ?></div>
        </div>
        <div style="background: #fef2f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 14px; text-align: center;">
            <div style="font-size: 11.5px; color: #991b1b; font-weight: 700;">مرفوضة</div>
            <div style="font-size: 20px; font-weight: 900; color: #dc2626;"><?php echo $stats['rejected']; ?></div>
        </div>
    </div>

    <!-- Search & Filter Controls Toolbar -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; padding: 16px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
        <form method="GET" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <input type="hidden" name="sm_tab" value="student-cards">

            <div style="flex: 2; min-width: 200px;">
                <input type="text" name="card_search" value="<?php echo esc_attr($search_q); ?>" placeholder="ابحث باسم الطالب، الكود، الهوية، أو الرقم المرجعي..." style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px;">
            </div>

            <div style="flex: 1; min-width: 150px;">
                <select name="status_filter" style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px;">
                    <option value="">جميع الحالات</option>
                    <option value="submitted" <?php selected($status_f, 'submitted'); ?>>مقدم جديد</option>
                    <option value="under_review" <?php selected($status_f, 'under_review'); ?>>قيد المراجعة</option>
                    <option value="parent_confirmation" <?php selected($status_f, 'parent_confirmation'); ?>>تأكيد ولي الأمر</option>
                    <option value="approved" <?php selected($status_f, 'approved'); ?>>مقبول ومعتمد</option>
                    <option value="preparing" <?php selected($status_f, 'preparing'); ?>>جاري الطباعة والتأهب</option>
                    <option value="issued" <?php selected($status_f, 'issued'); ?>>مصدّر ومفعّل</option>
                    <option value="rejected" <?php selected($status_f, 'rejected'); ?>>مرفوض</option>
                </select>
            </div>

            <?php if ($is_sys_admin): ?>
            <div style="flex: 1; min-width: 160px;">
                <select name="school_filter" style="width: 100%; height: 40px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px;">
                    <option value="">جميع المدارس والمؤسسات</option>
                    <?php foreach ($institutions as $inst_key => $inst_item): ?>
                    <option value="<?php echo esc_attr($inst_key); ?>" <?php selected($school_f, $inst_key); ?>><?php echo esc_html($inst_item['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <button type="submit" style="height: 40px; padding: 0 20px; background: #881337; color: white; border: none; border-radius: 8px; font-weight: 800; font-size: 12.5px; cursor: pointer;">تصفية البحث 🔍</button>
            <a href="?sm_tab=student-cards" style="height: 40px; padding: 0 16px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 800; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center;">إعادة تصفية ↺</a>
        </form>
    </div>

    <!-- Requests Table & Card Grid Container -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: right;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #334155;">
                    <th style="padding: 12px 14px; width: 60px;">الصورة</th>
                    <th style="padding: 12px 14px;">بيانات الطالب</th>
                    <th style="padding: 12px 14px;">الصف والشعبة</th>
                    <th style="padding: 12px 14px;">الرقم المرجعي</th>
                    <th style="padding: 12px 14px;">ولي الأمر والتواصل</th>
                    <th style="padding: 12px 14px;">حالة الطلب</th>
                    <th style="padding: 12px 14px;">تاريخ الطلب</th>
                    <th style="padding: 12px 14px; text-align: center;">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                <tr>
                    <td colspan="8" style="padding: 30px; text-align: center; color: #64748b; font-weight: 700;">لا توجد طلبات تصاريح خروج مطابقة لمعايير البحث الحالية.</td>
                </tr>
                <?php else: foreach ($requests as $r):
                    $photo = !empty($r->photo_url) ? $r->photo_url : SM_PLUGIN_URL . 'assets/images/default-avatar.png';
                    $status_label = SM_Public::eess_get_exit_card_status_label($r->status);
                    $status_badge_bg = '#f1f5f9'; $status_badge_color = '#334155';
                    if ($r->status === 'submitted') { $status_badge_bg = '#dcfce7'; $status_badge_color = '#166534'; }
                    elseif ($r->status === 'under_review') { $status_badge_bg = '#fefce8'; $status_badge_color = '#854d0e'; }
                    elseif ($r->status === 'approved') { $status_badge_bg = '#dbeafe'; $status_badge_color = '#1e40af'; }
                    elseif ($r->status === 'preparing') { $status_badge_bg = '#f3e8ff'; $status_badge_color = '#6b21a8'; }
                    elseif ($r->status === 'issued') { $status_badge_bg = '#ecfdf5'; $status_badge_color = '#065f46'; }
                    elseif ($r->status === 'rejected') { $status_badge_bg = '#fef2f2'; $status_badge_color = '#991b1b'; }
                ?>
                <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                    <td style="padding: 10px 14px;">
                        <img src="<?php echo esc_url($photo); ?>" style="width: 42px; height: 50px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;" alt="Student Avatar">
                    </td>
                    <td style="padding: 10px 14px;">
                        <div style="font-weight: 900; color: #0f172a;"><?php echo esc_html($r->student_name); ?></div>
                        <div style="font-size: 11px; color: #881337; font-weight: 800; font-family: monospace;"><?php echo esc_html($r->student_code ?: ('STU-' . $r->student_id)); ?></div>
                    </td>
                    <td style="padding: 10px 14px; font-weight: 700; color: #334155;">
                        <?php echo esc_html($r->class_name ?: 'الصف الدراسي'); ?> (<?php echo esc_html($r->section ?: 'أ'); ?>)
                    </td>
                    <td style="padding: 10px 14px; font-weight: 800; font-family: monospace; color: #0f172a;">
                        <?php echo esc_html($r->reference_no ?: ('EXT-' . date('Y') . '-' . $r->id)); ?>
                    </td>
                    <td style="padding: 10px 14px;">
                        <div style="font-weight: 700; color: #0f172a;"><?php echo esc_html($r->parent_name ?: 'ولي الأمر'); ?></div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 600;"><?php echo esc_html($r->parent_phone ?: '---'); ?></div>
                    </td>
                    <td style="padding: 10px 14px;">
                        <span style="display: inline-block; padding: 4px 10px; border-radius: 6px; background: <?php echo $status_badge_bg; ?>; color: <?php echo $status_badge_color; ?>; font-weight: 800; font-size: 11.5px;">
                            <?php echo esc_html($status_label); ?>
                        </span>
                    </td>
                    <td style="padding: 10px 14px; font-size: 11.5px; color: #64748b; font-weight: 600;">
                        <?php echo esc_html(date('Y-m-d H:i', strtotime($r->created_at))); ?>
                    </td>
                    <td style="padding: 10px 14px; text-align: center;">
                        <div style="display: flex; justify-content: center; gap: 6px;">
                            <button type="button" onclick="scInspectDetails(<?php echo $r->id; ?>)" style="height: 32px; padding: 0 12px; background: #0f172a; color: white; border: none; border-radius: 6px; font-weight: 800; font-size: 11.5px; cursor: pointer;">👁️ تدقيق</button>
                            <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=exit_permit_request&request_id=' . $r->id); ?>" target="_blank" style="height: 32px; padding: 0 12px; background: #16a34a; color: white; border-radius: 6px; font-weight: 800; font-size: 11.5px; text-decoration: none; display: inline-flex; align-items: center;">📄 طباعة A4</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Request Detail & Status Transition Modal -->
<div id="sc-details-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); z-index: 99999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; border-radius: 16px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); direction: rtl; font-family: 'Cairo', sans-serif;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #0f172a;">تفاصيل وإدارة طلب تصريح الخروج الرقمي</h3>
            <button type="button" onclick="document.getElementById('sc-details-modal').style.display='none'" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #64748b;">✕</button>
        </div>
        <div id="sc-modal-content">جاري تحميل البيانات... ⏳</div>
    </div>
</div>

<script>
function scInspectDetails(reqId) {
    const modal = document.getElementById('sc-details-modal');
    const content = document.getElementById('sc-modal-content');
    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align:center; padding:30px; font-weight:800; color:#64748b;">جاري جلب تفاصيل السجل والسيرة المعتمدة... ⏳</div>';

    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'sm_get_exit_card_request_details',
        request_id: reqId,
        nonce: '<?php echo wp_create_nonce("sm_admin_action"); ?>'
    }, function(res) {
        if (res.success && res.data) {
            const d = res.data;
            let html = '<div style="display:grid; grid-template-columns:1fr 2fr; gap:16px; margin-bottom:16px;">';
            html += '<div style="text-align:center;">';
            html += '<img src="' + (d.photo_url || '<?php echo SM_PLUGIN_URL . "assets/images/default-avatar.png"; ?>') + '" style="width:110px; height:130px; object-fit:cover; border-radius:10px; border:2px solid #cbd5e1; margin-bottom:6px;">';
            html += '<div style="font-size:11px; color:#881337; font-weight:900;">' + (d.student_code || '') + '</div>';
            html += '</div>';

            html += '<div style="font-size:12.5px; color:#334155; line-height:1.7;">';
            html += '<div style="font-size:15px; font-weight:900; color:#0f172a; margin-bottom:4px;">' + d.student_name + '</div>';
            html += '<div><strong>الصف والشعبة:</strong> ' + d.class_name + ' (' + d.section + ')</div>';
            html += '<div><strong>الهوية الوطنية:</strong> ' + (d.national_id || 'غير مسجلة') + '</div>';
            html += '<div><strong>ولي الأمر:</strong> ' + d.parent_name + ' (' + d.parent_phone + ')</div>';
            html += '<div><strong>الرقم المرجعي:</strong> <span style="color:#881337; font-weight:800;">' + d.reference_no + '</span></div>';
            html += '<div><strong>تاريخ الطلب:</strong> ' + d.created_at + '</div>';
            html += '</div></div>';

            if (d.signature_data) {
                html += '<div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:10px; margin-bottom:16px; text-align:center;">';
                html += '<div style="font-size:11.5px; font-weight:800; color:#64748b; margin-bottom:4px;">التوقيع الإلكتروني لولي الأمر المعتمد:</div>';
                html += '<img src="' + d.signature_data + '" style="max-height:60px; object-fit:contain;">';
                html += '</div>';
            }

            html += '<form method="POST" action="">';
            html += '<?php wp_nonce_field("sm_update_card_status_action", "sm_update_card_status_nonce"); ?>';
            html += '<input type="hidden" name="request_id" value="' + d.id + '">';

            html += '<div style="margin-bottom:12px;">';
            html += '<label style="font-size:12px; font-weight:800; color:#0f172a; display:block; margin-bottom:4px;">تعديل حالة الطلب والمرحلة:</label>';
            html += '<select name="new_status" style="width:100%; height:40px; border-radius:8px; border:1.5px solid #cbd5e1; padding:0 10px; font-size:12.5px; font-weight:700;">';
            html += '<option value="submitted" ' + (d.status==='submitted'?'selected':'') + '>مقدم جديد (Submitted)</option>';
            html += '<option value="under_review" ' + (d.status==='under_review'?'selected':'') + '>قيد المراجعة التدقيقية (Under Review)</option>';
            html += '<option value="parent_confirmation" ' + (d.status==='parent_confirmation'?'selected':'') + '>في انتظار تأكيد ولي الأمر (Parent Confirmation)</option>';
            html += '<option value="approved" ' + (d.status==='approved'?'selected':'') + '>مقبول ومعتمد رسمياً (Approved)</option>';
            html += '<option value="preparing" ' + (d.status==='preparing'?'selected':'') + '>جاري تجهيز وطباعة البطاقة (Preparing)</option>';
            html += '<option value="issued" ' + (d.status==='issued'?'selected':'') + '>تم الإصدار والتفعيل النهائي (Issued)</option>';
            html += '<option value="rejected" ' + (d.status==='rejected'?'selected':'') + '>مرفوض (Rejected)</option>';
            html += '</select></div>';

            html += '<div style="margin-bottom:16px;">';
            html += '<label style="font-size:12px; font-weight:800; color:#0f172a; display:block; margin-bottom:4px;">ملاحظات الإدارة والمراجعة:</label>';
            html += '<textarea name="admin_notes" style="width:100%; height:60px; border-radius:8px; border:1px solid #cbd5e1; padding:8px; font-size:12px;">' + (d.admin_notes || '') + '</textarea>';
            html += '</div>';

            html += '<div style="display:flex; justify-content:flex-end; gap:10px;">';
            html += '<button type="button" onclick="document.getElementById(\'sc-details-modal\').style.display=\'none\'" style="height:38px; padding:0 16px; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; border-radius:8px; font-weight:800; font-size:12px; cursor:pointer;">إلغاء</button>';
            html += '<button type="submit" style="height:38px; padding:0 22px; background:#881337; color:white; border:none; border-radius:8px; font-weight:800; font-size:12px; cursor:pointer;">تحديث حالة الطلب ✓</button>';
            html += '</div></form>';

            content.innerHTML = html;
        } else {
            content.innerHTML = '<div style="color:#dc2626; font-weight:800;">تعذر تحميل تفاصيل الطلب.</div>';
        }
    });
}
</script>
