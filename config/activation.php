<?php

return [

    /*
    | الحد الأقصى لعدد مرات إرسال بريد التفعيل (يشمل الإرسال الأول)
    */
    'max_resends' => (int) env('ACTIVATION_MAX_RESENDS', 5),

    /*
    | أقل مدة زمنية (بالدقائق) بين كل إرسال وآخر
    */
    'resend_interval_minutes' => (int) env('ACTIVATION_RESEND_INTERVAL_MINUTES', 2),

];
