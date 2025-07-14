<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // تنظیمات عمومی
            [
                'setting_key' => 'site_name',
                'setting_value' => 'هومینکس - سامانه املاک',
                'setting_type' => 'string',
                'category' => 'general',
                'description' => 'نام سایت',
                'is_public' => true,
            ],
            [
                'setting_key' => 'site_description',
                'setting_value' => 'بزرگترین سامانه خرید و فروش املاک در ایران',
                'setting_type' => 'text',
                'category' => 'general',
                'description' => 'توضیحات سایت',
                'is_public' => true,
            ],
            [
                'setting_key' => 'site_keywords',
                'setting_value' => 'املاک، خرید خانه، فروش ملک، اجاره آپارتمان، ویلا',
                'setting_type' => 'text',
                'category' => 'general',
                'description' => 'کلمات کلیدی سایت',
                'is_public' => true,
            ],
            [
                'setting_key' => 'contact_email',
                'setting_value' => 'info@hominex.ir',
                'setting_type' => 'string',
                'category' => 'contact',
                'description' => 'ایمیل تماس',
                'is_public' => true,
            ],
            [
                'setting_key' => 'contact_phone',
                'setting_value' => '021-88776655',
                'setting_type' => 'string',
                'category' => 'contact',
                'description' => 'شماره تماس',
                'is_public' => true,
            ],
            [
                'setting_key' => 'contact_address',
                'setting_value' => 'تهران، خیابان ولیعصر، پلاک 123',
                'setting_type' => 'text',
                'category' => 'contact',
                'description' => 'آدرس شرکت',
                'is_public' => true,
            ],

            // تنظیمات املاک
            [
                'setting_key' => 'max_property_images',
                'setting_value' => '20',
                'setting_type' => 'integer',
                'category' => 'property',
                'description' => 'حداکثر تعداد تصاویر هر ملک',
                'is_public' => false,
            ],
            [
                'setting_key' => 'featured_property_duration',
                'setting_value' => '30',
                'setting_type' => 'integer',
                'category' => 'property',
                'description' => 'مدت زمان ویژه بودن ملک (روز)',
                'is_public' => false,
            ],
            [
                'setting_key' => 'auto_approve_properties',
                'setting_value' => 'false',
                'setting_type' => 'boolean',
                'category' => 'property',
                'description' => 'تأیید خودکار املاک',
                'is_public' => false,
            ],
            [
                'setting_key' => 'min_property_price',
                'setting_value' => '100000000',
                'setting_type' => 'integer',
                'category' => 'property',
                'description' => 'حداقل قیمت ملک (ریال)',
                'is_public' => false,
            ],

            // تنظیمات کاربران
            [
                'setting_key' => 'auto_verify_consultants',
                'setting_value' => 'false',
                'setting_type' => 'boolean',
                'category' => 'user',
                'description' => 'تأیید خودکار مشاورین',
                'is_public' => false,
            ],
            [
                'setting_key' => 'max_favorites_per_user',
                'setting_value' => '50',
                'setting_type' => 'integer',
                'category' => 'user',
                'description' => 'حداکثر تعداد علاقه‌مندی هر کاربر',
                'is_public' => false,
            ],
            [
                'setting_key' => 'otp_expiry_minutes',
                'setting_value' => '10',
                'setting_type' => 'integer',
                'category' => 'user',
                'description' => 'مدت اعتبار کد تأیید (دقیقه)',
                'is_public' => false,
            ],

            // تنظیمات پیامک
            [
                'setting_key' => 'sms_enabled',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'category' => 'sms',
                'description' => 'فعال بودن سرویس پیامک',
                'is_public' => false,
            ],
            [
                'setting_key' => 'welcome_sms_enabled',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'category' => 'sms',
                'description' => 'ارسال پیامک خوش‌آمدگویی',
                'is_public' => false,
            ],

            // تنظیمات اپلیکیشن
            [
                'setting_key' => 'maintenance_mode',
                'setting_value' => 'false',
                'setting_type' => 'boolean',
                'category' => 'app',
                'description' => 'حالت تعمیر و نگهداری',
                'is_public' => true,
            ],
            [
                'setting_key' => 'maintenance_message',
                'setting_value' => 'سایت در حال تعمیر و نگهداری است. لطفا بعداً مراجعه کنید.',
                'setting_type' => 'text',
                'category' => 'app',
                'description' => 'پیام حالت تعمیر',
                'is_public' => true,
            ],
            [
                'setting_key' => 'api_rate_limit',
                'setting_value' => '100',
                'setting_type' => 'integer',
                'category' => 'app',
                'description' => 'محدودیت درخواست API (در دقیقه)',
                'is_public' => false,
            ],

            // تنظیمات شبکه‌های اجتماعی
            [
                'setting_key' => 'telegram_channel',
                'setting_value' => '@hominex_ir',
                'setting_type' => 'string',
                'category' => 'social',
                'description' => 'کانال تلگرام',
                'is_public' => true,
            ],
            [
                'setting_key' => 'instagram_page',
                'setting_value' => 'hominex.ir',
                'setting_type' => 'string',
                'category' => 'social',
                'description' => 'صفحه اینستاگرام',
                'is_public' => true,
            ],
            [
                'setting_key' => 'whatsapp_number',
                'setting_value' => '09123456789',
                'setting_type' => 'string',
                'category' => 'social',
                'description' => 'شماره واتساپ',
                'is_public' => true,
            ],

            // تنظیمات SEO
            [
                'setting_key' => 'google_analytics_id',
                'setting_value' => '',
                'setting_type' => 'string',
                'category' => 'seo',
                'description' => 'شناسه گوگل آنالیتیکس',
                'is_public' => false,
            ],
            [
                'setting_key' => 'google_tag_manager_id',
                'setting_value' => '',
                'setting_type' => 'string',
                'category' => 'seo',
                'description' => 'شناسه گوگل تگ منیجر',
                'is_public' => false,
            ],

            // تنظیمات مالی
            [
                'setting_key' => 'commission_rate',
                'setting_value' => '2.5',
                'setting_type' => 'string',
                'category' => 'financial',
                'description' => 'نرخ کمیسیون (درصد)',
                'is_public' => false,
            ],
            [
                'setting_key' => 'featured_property_price',
                'setting_value' => '500000',
                'setting_type' => 'integer',
                'category' => 'financial',
                'description' => 'هزینه ویژه کردن ملک (تومان)',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            SiteSetting::create($setting);
        }
    }
}
