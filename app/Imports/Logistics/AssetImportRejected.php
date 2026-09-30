<?php

namespace App\Imports\Logistics;

use RuntimeException;

/** رفض استيراد قبل أي كتابة (صف خارج نطاق صلاحية الإنشاء)؛ الرسالة عربية وتذكر رقم الصف. */
class AssetImportRejected extends RuntimeException {}
