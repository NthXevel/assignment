<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductCategory;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $categories = ProductCategory::all();
        
        foreach ($categories as $category) {
            $this->seedProductsForCategory($category);
        }
    }
    
    private function seedProductsForCategory(ProductCategory $category)
    {
        switch ($category->slug) {
            case 'smartphones-accessories':
                $this->seedSmartphones($category);
                break;
            case 'computers-peripherals':
                $this->seedComputers($category);
                break;
            case 'home-entertainment':
                $this->seedHomeEntertainment($category);
                break;
            case 'wearables-smart-devices':
                $this->seedWearables($category);
                break;
        }
    }
    
    private function seedSmartphones(ProductCategory $category)
    {
        $smartphones = [
            [
                'name' => 'iPhone 15 Pro',
                'model' => 'A3101',
                'sku' => 'IPH-15P-128-TIT',
                'cost_price' => 4200.00,
                'selling_price' => 4999.00,
                'description' => 'Latest iPhone with titanium design, A17 Pro chip, and Pro camera system',
                'specifications' => [
                    'storage' => '128GB',
                    'color' => 'Natural Titanium',
                    'display_size' => '6.1 inch',
                    'camera' => '48MP Main + 12MP Ultra Wide + 12MP Telephoto',
                    'battery_life' => 'Up to 23 hours video playback',
                    'operating_system' => 'iOS 17',
                    'connectivity' => '5G, Wi-Fi 6E, Bluetooth 5.3'
                ]
            ],
            [
                'name' => 'iPhone 15',
                'model' => 'A3090',
                'sku' => 'IPH-15-128-BLU',
                'cost_price' => 3200.00,
                'selling_price' => 3799.00,
                'description' => 'iPhone 15 with A16 Bionic chip and Dynamic Island',
                'specifications' => [
                    'storage' => '128GB',
                    'color' => 'Blue',
                    'display_size' => '6.1 inch',
                    'camera' => '48MP Main + 12MP Ultra Wide',
                    'battery_life' => 'Up to 20 hours video playback',
                    'operating_system' => 'iOS 17',
                    'connectivity' => '5G, Wi-Fi 6, Bluetooth 5.3'
                ]
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'model' => 'SM-S928B',
                'sku' => 'SAM-S24U-256-BLK',
                'cost_price' => 4500.00,
                'selling_price' => 5299.00,
                'description' => 'Premium Samsung flagship with S Pen, 200MP camera, and AI features',
                'specifications' => [
                    'storage' => '256GB',
                    'color' => 'Titanium Black',
                    'display_size' => '6.8 inch Dynamic AMOLED 2X',
                    'camera' => '200MP Main + 50MP Periscope + 12MP Ultra Wide + 10MP Telephoto',
                    'battery' => '5000mAh',
                    'operating_system' => 'Android 14',
                    'special_features' => 'S Pen included, AI photo editing'
                ]
            ],
            [
                'name' => 'Samsung Galaxy S24',
                'model' => 'SM-S921B',
                'sku' => 'SAM-S24-128-VIO',
                'cost_price' => 2800.00,
                'selling_price' => 3299.00,
                'description' => 'Latest Galaxy S series with advanced AI capabilities',
                'specifications' => [
                    'storage' => '128GB',
                    'color' => 'Cobalt Violet',
                    'display_size' => '6.2 inch Dynamic AMOLED 2X',
                    'camera' => '50MP Main + 12MP Ultra Wide + 10MP Telephoto',
                    'battery' => '4000mAh',
                    'operating_system' => 'Android 14',
                    'special_features' => 'Circle to Search, Live Translate'
                ]
            ],
            [
                'name' => 'Google Pixel 8 Pro',
                'model' => 'GC3VE',
                'sku' => 'GOO-P8P-128-OBS',
                'cost_price' => 3500.00,
                'selling_price' => 4199.00,
                'description' => 'Google flagship with Tensor G3 chip and advanced computational photography',
                'specifications' => [
                    'storage' => '128GB',
                    'color' => 'Obsidian',
                    'display_size' => '6.7 inch LTPO OLED',
                    'camera' => '50MP Main + 48MP Ultra Wide + 48MP Telephoto',
                    'battery' => '5050mAh',
                    'operating_system' => 'Android 14',
                    'special_features' => 'Magic Eraser, Best Take, Audio Magic Eraser'
                ]
            ],
            // Accessories
            [
                'name' => 'Apple AirPods Pro (2nd Gen)',
                'model' => 'MTJV3',
                'sku' => 'APP-AIRP-PRO2-WHT',
                'cost_price' => 850.00,
                'selling_price' => 999.00,
                'description' => 'Wireless earbuds with active noise cancellation and spatial audio',
                'specifications' => [
                    'color' => 'White',
                    'battery_life' => 'Up to 6 hours with ANC',
                    'features' => 'Active Noise Cancellation, Transparency mode',
                    'charging_case' => 'MagSafe and Lightning compatible',
                    'connectivity' => 'Bluetooth 5.3'
                ]
            ],
            [
                'name' => 'Samsung Galaxy Buds2 Pro',
                'model' => 'SM-R510',
                'sku' => 'SAM-GB2P-GRA',
                'cost_price' => 650.00,
                'selling_price' => 799.00,
                'description' => 'Premium wireless earbuds with intelligent ANC',
                'specifications' => [
                    'color' => 'Graphite',
                    'battery_life' => 'Up to 5 hours with ANC',
                    'features' => 'Intelligent ANC, 360 Audio',
                    'water_resistance' => 'IPX7',
                    'connectivity' => 'Bluetooth 5.3'
                ]
            ],
            [
                'name' => 'Anker PowerCore 10000',
                'model' => 'A1263011',
                'sku' => 'ANK-PC10K-BLK',
                'cost_price' => 120.00,
                'selling_price' => 159.00,
                'description' => 'Compact portable charger with high-speed charging',
                'specifications' => [
                    'capacity' => '10000mAh',
                    'color' => 'Black',
                    'output_ports' => '2 USB-A ports',
                    'charging_speed' => 'PowerIQ 2.0',
                    'weight' => '180g'
                ]
            ],
            [
                'name' => 'Spigen Tough Armor Case - iPhone 15 Pro',
                'model' => 'ACS05555',
                'sku' => 'SPI-TAC-I15P-BLK',
                'cost_price' => 95.00,
                'selling_price' => 129.00,
                'description' => 'Heavy-duty protection case with kickstand',
                'specifications' => [
                    'compatibility' => 'iPhone 15 Pro',
                    'color' => 'Black',
                    'material' => 'PC + TPU',
                    'features' => 'Kickstand, Raised bezels',
                    'drop_protection' => 'Military grade'
                ]
            ],
            [
                'name' => 'Belkin 3-in-1 Wireless Charger',
                'model' => 'WIZ017myWH',
                'sku' => 'BEL-3IN1-WC-WHT',
                'cost_price' => 380.00,
                'selling_price' => 459.00,
                'description' => 'MagSafe compatible wireless charging station',
                'specifications' => [
                    'compatibility' => 'iPhone, Apple Watch, AirPods',
                    'color' => 'White',
                    'charging_speed' => '15W MagSafe',
                    'features' => 'LED status indicators',
                    'cable_length' => '1.2m'
                ]
            ]
        ];
        
        foreach ($smartphones as $product) {
            $product['category_id'] = $category->id;
            Product::create($product);
        }
    }
    
    private function seedComputers(ProductCategory $category)
    {
        $computers = [
            [
                'name' => 'MacBook Air 13-inch M3',
                'model' => 'MRXN3',
                'sku' => 'APL-MBA13-M3-256-MID',
                'cost_price' => 4200.00,
                'selling_price' => 4999.00,
                'description' => 'Ultra-portable laptop with M3 chip and all-day battery life',
                'specifications' => [
                    'processor' => 'Apple M3 chip',
                    'memory' => '8GB unified memory',
                    'storage' => '256GB SSD',
                    'display' => '13.6-inch Liquid Retina',
                    'color' => 'Midnight',
                    'battery_life' => 'Up to 18 hours',
                    'operating_system' => 'macOS Sonoma'
                ]
            ],
            [
                'name' => 'MacBook Pro 14-inch M3 Pro',
                'model' => 'MRX33',
                'sku' => 'APL-MBP14-M3P-512-SLV',
                'cost_price' => 7200.00,
                'selling_price' => 8499.00,
                'description' => 'Professional laptop with M3 Pro chip for demanding workflows',
                'specifications' => [
                    'processor' => 'Apple M3 Pro chip',
                    'memory' => '18GB unified memory',
                    'storage' => '512GB SSD',
                    'display' => '14.2-inch Liquid Retina XDR',
                    'color' => 'Silver',
                    'battery_life' => 'Up to 18 hours',
                    'ports' => '3x Thunderbolt 4, HDMI, SD card'
                ]
            ],
            [
                'name' => 'Dell XPS 13 Plus',
                'model' => '9320',
                'sku' => 'DEL-XPS13P-I7-512-SLV',
                'cost_price' => 4800.00,
                'selling_price' => 5699.00,
                'description' => 'Premium ultrabook with stunning InfinityEdge display',
                'specifications' => [
                    'processor' => 'Intel Core i7-1360P',
                    'memory' => '16GB LPDDR5',
                    'storage' => '512GB PCIe SSD',
                    'display' => '13.4-inch FHD+ InfinityEdge',
                    'color' => 'Platinum Silver',
                    'operating_system' => 'Windows 11 Pro',
                    'features' => 'Haptic touchpad, capacitive function keys'
                ]
            ],
            [
                'name' => 'HP Spectre x360 14',
                'model' => '14-ef2013dx',
                'sku' => 'HP-SP360-14-I7-BLU',
                'cost_price' => 4200.00,
                'selling_price' => 4999.00,
                'description' => '2-in-1 convertible laptop with OLED display',
                'specifications' => [
                    'processor' => 'Intel Core i7-1355U',
                    'memory' => '16GB LPDDR4x',
                    'storage' => '1TB PCIe SSD',
                    'display' => '13.5-inch 3K2K OLED Touch',
                    'color' => 'Nocturne Blue',
                    'operating_system' => 'Windows 11 Home',
                    'features' => '360-degree hinge, HP Pen included'
                ]
            ],
            [
                'name' => 'ASUS ROG Strix G16',
                'model' => 'G614JV',
                'sku' => 'ASU-ROGS16-RTX4060-GRA',
                'cost_price' => 5500.00,
                'selling_price' => 6499.00,
                'description' => 'Gaming laptop with RTX 4060 and high refresh display',
                'specifications' => [
                    'processor' => 'Intel Core i7-13650HX',
                    'memory' => '16GB DDR5',
                    'storage' => '512GB PCIe 4.0 SSD',
                    'graphics' => 'NVIDIA GeForce RTX 4060',
                    'display' => '16-inch FHD 165Hz',
                    'color' => 'Eclipse Gray',
                    'operating_system' => 'Windows 11 Home'
                ]
            ],
            // Peripherals
            [
                'name' => 'Logitech MX Master 3S',
                'model' => '910-006556',
                'sku' => 'LOG-MXM3S-GRA',
                'cost_price' => 350.00,
                'selling_price' => 429.00,
                'description' => 'Advanced wireless mouse with precision scrolling',
                'specifications' => [
                    'color' => 'Graphite',
                    'connectivity' => 'Bluetooth, USB-C receiver',
                    'battery_life' => 'Up to 70 days',
                    'features' => 'MagSpeed scrolling, 8K DPI sensor',
                    'compatibility' => 'Windows, macOS, Linux'
                ]
            ],
            [
                'name' => 'Keychron K2 V2',
                'model' => 'K2V2-A1',
                'sku' => 'KEY-K2V2-RGB-BLU',
                'cost_price' => 280.00,
                'selling_price' => 349.00,
                'description' => 'Compact mechanical keyboard for Mac and Windows',
                'specifications' => [
                    'layout' => '75% compact layout',
                    'switches' => 'Gateron Blue',
                    'connectivity' => 'Wireless + Wired',
                    'backlight' => 'RGB',
                    'battery_life' => 'Up to 240 hours',
                    'compatibility' => 'Mac and Windows'
                ]
            ],
            [
                'name' => 'LG UltraWide 34WP65C-B',
                'model' => '34WP65C-B',
                'sku' => 'LG-UW34-WQHD-BLK',
                'cost_price' => 1200.00,
                'selling_price' => 1449.00,
                'description' => '34-inch curved ultrawide monitor with USB-C',
                'specifications' => [
                    'screen_size' => '34 inch',
                    'resolution' => '3440x1440 WQHD',
                    'panel_type' => 'IPS',
                    'curvature' => '1800R',
                    'refresh_rate' => '160Hz',
                    'connectivity' => 'USB-C, HDMI, DisplayPort',
                    'features' => 'HDR10, FreeSync Premium'
                ]
            ],
            [
                'name' => 'SanDisk Extreme Portable SSD 1TB',
                'model' => 'SDSSDE61-1T00',
                'sku' => 'SAN-EXT-SSD-1TB-BLK',
                'cost_price' => 420.00,
                'selling_price' => 519.00,
                'description' => 'High-speed portable SSD with rugged design',
                'specifications' => [
                    'capacity' => '1TB',
                    'interface' => 'USB 3.2 Gen 2',
                    'read_speed' => 'Up to 1,050 MB/s',
                    'durability' => 'IP65 water/dust resistant',
                    'color' => 'Black',
                    'encryption' => '256-bit AES hardware encryption'
                ]
            ],
            [
                'name' => 'Razer DeathAdder V3',
                'model' => 'RZ01-04910100',
                'sku' => 'RAZ-DAV3-BLK',
                'cost_price' => 220.00,
                'selling_price' => 279.00,
                'description' => 'Ergonomic gaming mouse with Focus Pro sensor',
                'specifications' => [
                    'sensor' => 'Focus Pro 30K',
                    'max_dpi' => '30,000',
                    'connectivity' => 'Wired USB-A',
                    'switches' => 'Razer Optical 90M clicks',
                    'weight' => '59g',
                    'color' => 'Black'
                ]
            ]
        ];
        
        foreach ($computers as $product) {
            $product['category_id'] = $category->id;
            Product::create($product);
        }
    }
    
    private function seedHomeEntertainment(ProductCategory $category)
    {
        $homeEntertainment = [
            [
                'name' => 'Samsung 65" Neo QLED 4K QN90D',
                'model' => 'QA65QN90DAKXXT',
                'sku' => 'SAM-TV65-QN90D-BLK',
                'cost_price' => 6800.00,
                'selling_price' => 7999.00,
                'description' => 'Premium 4K Neo QLED TV with Quantum Matrix Technology',
                'specifications' => [
                    'screen_size' => '65 inch',
                    'resolution' => '4K UHD (3840 x 2160)',
                    'display_type' => 'Neo QLED',
                    'hdr' => 'HDR10+, HLG',
                    'smart_platform' => 'Tizen OS',
                    'connectivity' => '4x HDMI 2.1, 2x USB, Wi-Fi 6',
                    'features' => 'Object Tracking Sound+, Gaming Hub'
                ]
            ],
            [
                'name' => 'LG 55" OLED C3 Series',
                'model' => 'OLED55C3PSA',
                'sku' => 'LG-TV55-C3-OLED-BLK',
                'cost_price' => 4200.00,
                'selling_price' => 4999.00,
                'description' => 'Self-lit OLED TV with perfect blacks and infinite contrast',
                'specifications' => [
                    'screen_size' => '55 inch',
                    'resolution' => '4K UHD (3840 x 2160)',
                    'display_type' => 'OLED evo',
                    'processor' => 'α9 Gen6 AI Processor 4K',
                    'hdr' => 'Dolby Vision IQ, HDR10, HLG',
                    'smart_platform' => 'webOS 23',
                    'features' => 'Dolby Atmos, NVIDIA G-SYNC, AMD FreeSync'
                ]
            ],
            [
                'name' => 'Sony 75" BRAVIA XR X90L',
                'model' => 'XR75X90L',
                'sku' => 'SON-TV75-X90L-BLK',
                'cost_price' => 7500.00,
                'selling_price' => 8799.00,
                'description' => 'Full Array LED TV with Cognitive Processor XR',
                'specifications' => [
                    'screen_size' => '75 inch',
                    'resolution' => '4K UHD (3840 x 2160)',
                    'display_type' => 'Full Array LED',
                    'processor' => 'Cognitive Processor XR',
                    'hdr' => 'Dolby Vision, HDR10, HLG',
                    'smart_platform' => 'Google TV',
                    'features' => 'Acoustic Multi-Audio, Perfect for PS5'
                ]
            ],
            [
                'name' => 'Sonos Arc Soundbar',
                'model' => 'ARCG1US1',
                'sku' => 'SON-ARC-SB-BLK',
                'cost_price' => 2800.00,
                'selling_price' => 3299.00,
                'description' => 'Premium smart soundbar with Dolby Atmos',
                'specifications' => [
                    'channels' => '5.0.2',
                    'audio_formats' => 'Dolby Atmos, TrueHD, DTS',
                    'connectivity' => 'eARC/ARC HDMI, Wi-Fi, Ethernet',
                    'voice_control' => 'Amazon Alexa, Google Assistant',
                    'color' => 'Black',
                    'features' => 'Trueplay tuning, AirPlay 2'
                ]
            ],
            [
                'name' => 'Bose SoundLink Revolve+ II',
                'model' => '858365',
                'sku' => 'BOS-SLR2-BLK',
                'cost_price' => 880.00,
                'selling_price' => 1099.00,
                'description' => 'Portable Bluetooth speaker with 360-degree sound',
                'specifications' => [
                    'connectivity' => 'Bluetooth 5.1',
                    'battery_life' => 'Up to 17 hours',
                    'water_resistance' => 'IP55',
                    'color' => 'Triple Black',
                    'features' => 'Speakerphone, Party Mode',
                    'weight' => '0.9 kg'
                ]
            ],
            [
                'name' => 'PlayStation 5 Console',
                'model' => 'CFI-1200A01',
                'sku' => 'SON-PS5-STD-WHT',
                'cost_price' => 1800.00,
                'selling_price' => 2099.00,
                'description' => 'Next-gen gaming console with ultra-high speed SSD',
                'specifications' => [
                    'cpu' => 'AMD Zen 2, 8 cores @ 3.5GHz',
                    'gpu' => 'AMD RDNA 2, 10.28 TFLOPs',
                    'memory' => '16GB GDDR6',
                    'storage' => '825GB SSD',
                    'optical_drive' => 'Ultra HD Blu-ray',
                    'color' => 'White',
                    'features' => 'Ray tracing, 3D Audio, DualSense controller'
                ]
            ],
            [
                'name' => 'Xbox Series X',
                'model' => 'RRT-00001',
                'sku' => 'MIC-XSX-1TB-BLK',
                'cost_price' => 1900.00,
                'selling_price' => 2199.00,
                'description' => 'Most powerful Xbox with 4K gaming and Quick Resume',
                'specifications' => [
                    'cpu' => 'AMD Zen 2, 8 cores @ 3.8GHz',
                    'gpu' => 'AMD RDNA 2, 12 TFLOPs',
                    'memory' => '16GB GDDR6',
                    'storage' => '1TB NVMe SSD',
                    'optical_drive' => '4K UHD Blu-ray',
                    'color' => 'Matte Black',
                    'features' => 'Smart Delivery, Auto HDR, Variable Refresh Rate'
                ]
            ],
            [
                'name' => 'Apple TV 4K (3rd Gen)',
                'model' => 'MN893',
                'sku' => 'APL-ATV4K-128-BLK',
                'cost_price' => 650.00,
                'selling_price' => 799.00,
                'description' => 'Streaming device with A15 Bionic chip and Siri Remote',
                'specifications' => [
                    'processor' => 'A15 Bionic chip',
                    'storage' => '128GB',
                    'video' => '4K HDR10+, Dolby Vision',
                    'audio' => 'Dolby Atmos',
                    'connectivity' => 'Wi-Fi 6, Bluetooth 5.0, Ethernet',
                    'color' => 'Black',
                    'features' => 'Siri Remote, AirPlay, HomeKit Hub'
                ]
            ],
            [
                'name' => 'Roku Ultra',
                'model' => '4802R',
                'sku' => 'ROK-ULTRA-4K-BLK',
                'cost_price' => 350.00,
                'selling_price' => 429.00,
                'description' => 'Premium streaming player with Dolby Vision',
                'specifications' => [
                    'video' => '4K, HDR10, HDR10+, Dolby Vision',
                    'audio' => 'Dolby Atmos',
                    'connectivity' => 'Dual-band Wi-Fi, Ethernet, USB',
                    'remote' => 'Voice Remote Pro with rechargeable battery',
                    'features' => 'Lost remote finder, private listening',
                    'color' => 'Black'
                ]
            ],
            [
                'name' => 'JBL Bar 5.1 Surround',
                'model' => 'JBLBAR51BLKEP',
                'sku' => 'JBL-BAR51-SUR-BLK',
                'cost_price' => 1800.00,
                'selling_price' => 2199.00,
                'description' => 'Soundbar with detachable surround speakers',
                'specifications' => [
                    'channels' => '5.1',
                    'total_power' => '550W',
                    'subwoofer' => '10-inch wireless',
                    'connectivity' => 'HDMI ARC, Bluetooth, USB',
                    'surround_speakers' => 'Detachable battery-powered',
                    'color' => 'Black',
                    'features' => 'MultiBeam technology, Chromecast built-in'
                ]
            ]
        ];
        
        foreach ($homeEntertainment as $product) {
            $product['category_id'] = $category->id;
            Product::create($product);
        }
    }
    
    private function seedWearables(ProductCategory $category)
    {
        $wearables = [
            [
                'name' => 'Apple Watch Series 9 GPS',
                'model' => 'MR8U3',
                'sku' => 'APL-AW9-41-MID-SP',
                'cost_price' => 1400.00,
                'selling_price' => 1699.00,
                'description' => 'Smartwatch with S9 chip and Double Tap gesture',
                'specifications' => [
                    'case_size' => '41mm',
                    'case_material' => 'Aluminum',
                    'color' => 'Midnight',
                    'band' => 'Sport Band',
                    'display' => 'Always-On Retina',
                    'water_resistance' => '50 meters',
                    'battery_life' => 'Up to 18 hours',
                    'features' => 'ECG, Blood Oxygen, Double Tap, Crash Detection'
                ]
            ],
            [
                'name' => 'Samsung Galaxy Watch6 Classic',
                'model' => 'SM-R950F',
                'sku' => 'SAM-GW6C-43-BLK-LE',
                'cost_price' => 1200.00,
                'selling_price' => 1449.00,
                'description' => 'Premium smartwatch with rotating bezel',
                'specifications' => [
                    'case_size' => '43mm',
                    'case_material' => 'Stainless Steel',
                    'color' => 'Black',
                    'band' => 'Leather',
                    'display' => '1.3-inch Super AMOLED',
                    'water_resistance' => '5ATM + IP68',
                    'battery_life' => 'Up to 40 hours',
                    'features' => 'Rotating Bezel, Body Composition, Sleep Coaching'
                ]
            ],
            [
                'name' => 'Garmin Forerunner 965',
                'model' => '010-02809-10',
                'sku' => 'GAR-FR965-BLK-SIL',
                'cost_price' => 1950.00,
                'selling_price' => 2299.00,
                'description' => 'Premium GPS running smartwatch with AMOLED display',
                'specifications' => [
                    'case_size' => '47mm',
                    'case_material' => 'Fiber-reinforced polymer',
                    'color' => 'Black/Silver',
                    'display' => '1.4-inch AMOLED',
                    'water_resistance' => '5ATM',
                    'battery_life' => 'Up to 23 days',
                    'features' => 'Multi-band GPS, Training Readiness, HRV Status'
                ]
            ],
            [
                'name' => 'Fitbit Charge 6',
                'model' => 'FB422',
                'sku' => 'FIT-CH6-BLK-SIL',
                'cost_price' => 650.00,
                'selling_price' => 799.00,
                'description' => 'Advanced fitness tracker with built-in GPS',
                'specifications' => [
                    'display' => 'Color AMOLED',
                    'color' => 'Obsidian/Silver',
                    'water_resistance' => '50 meters',
                    'battery_life' => 'Up to 7 days',
                    'features' => 'Built-in GPS, Google apps, 40+ exercise modes',
                    'health_tracking' => 'Heart rate, SpO2, stress, sleep'
                ]
            ],
            [
                'name' => 'Amazfit GTR 4',
                'model' => 'A2168',
                'sku' => 'AMZ-GTR4-46-BLK',
                'cost_price' => 580.00,
                'selling_price' => 729.00,
                'description' => 'Premium smartwatch with 14-day battery life',
                'specifications' => [
                    'case_size' => '46mm',
                    'case_material' => 'Aluminum alloy',
                    'color' => 'Supersonic Black',
                    'display' => '1.43-inch AMOLED',
                    'water_resistance' => '5ATM',
                    'battery_life' => 'Up to 14 days',
                    'features' => 'Dual-band GPS, 150+ sports modes, Zepp OS'
                ]
            ],
            [
                'name' => 'WHOOP 4.0',
                'model' => 'WHOOP4',
                'sku' => 'WHO-4.0-BLK',
                'cost_price' => 850.00,
                'selling_price' => 999.00,
                'description' => 'Screenless fitness tracker focused on recovery and strain',
                'specifications' => [
                    'sensors' => '5-LED, 4-photodiode configuration',
                    'color' => 'Black',
                    'battery_life' => '4-5 days',
                    'water_resistance' => 'Waterproof',
                    'features' => 'Strain Coach, Recovery Score, Sleep Coach',
                    'subscription' => 'WHOOP membership required'
                ]
            ],
            // Smart Home Devices
            [
                'name' => 'Amazon Echo Dot (5th Gen)',
                'model' => 'B09B8V1LZ3',
                'sku' => 'AMZ-ECHO5-DOT-CHA',
                'cost_price' => 180.00,
                'selling_price' => 229.00,
                'description' => 'Smart speaker with Alexa and improved audio',
                'specifications' => [
                    'voice_assistant' => 'Amazon Alexa',
                    'color' => 'Charcoal',
                    'connectivity' => 'Wi-Fi, Bluetooth',
                    'audio' => 'Bigger, fuller sound',
                    'features' => 'Tap to snooze, Temperature sensor',
                    'smart_home' => 'Built-in Zigbee hub'
                ]
            ],
            [
                'name' => 'Google Nest Hub (2nd Gen)',
                'model' => 'GA01892-US',
                'sku' => 'GOO-NH2-CHA',
                'cost_price' => 320.00,
                'selling_price' => 399.00,
                'description' => 'Smart display with sleep sensing and Google Assistant',
                'specifications' => [
                    'screen_size' => '7-inch touchscreen',
                    'voice_assistant' => 'Google Assistant',
                    'color' => 'Charcoal',
                    'features' => 'Sleep Sensing, Face Match, Gesture control',
                    'connectivity' => 'Wi-Fi, Bluetooth',
                    'camera' => 'No camera for privacy'
                ]
            ],
            [
                'name' => 'Ring Video Doorbell Pro 2',
                'model' => '8VR1S7-0EU0',
                'sku' => 'RIN-VDB-PRO2-BLK',
                'cost_price' => 780.00,
                'selling_price' => 949.00,
                'description' => 'Advanced video doorbell with 3D Motion Detection',
                'specifications' => [
                    'video_quality' => '1536p HD',
                    'field_of_view' => '150° horizontal, 90° vertical',
                    'color' => 'Satin Nickel',
                    'features' => '3D Motion Detection, Pre-Roll, Bird\'s Eye View',
                    'connectivity' => 'Dual-band Wi-Fi',
                    'power' => 'Hardwired installation required'
                ]
            ],
            [
                'name' => 'Philips Hue White and Color Ambiance Starter Kit',
                'model' => '548487',
                'sku' => 'PHI-HUE-START-A19',
                'cost_price' => 650.00,
                'selling_price' => 799.00,
                'description' => 'Smart lighting starter kit with bridge and 3 bulbs',
                'specifications' => [
                    'bulbs_included' => '3x A19 White and Color bulbs',
                    'hub_included' => 'Philips Hue Bridge',
                    'colors' => '16 million colors',
                    'brightness' => '800 lumens per bulb',
                    'connectivity' => 'Zigbee 3.0',
                    'compatibility' => 'Alexa, Google Assistant, Apple HomeKit'
                ]
            ]
        ];
        
        foreach ($wearables as $product) {
            $product['category_id'] = $category->id;
            Product::create($product);
        }
    }
}

