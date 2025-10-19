<?php

namespace App\Services;

use App\Mail\LicenseExpirationAlert;
use App\Mail\LowStockAlert;
use App\Models\License;
use App\Models\StationaryItem;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Check for expiring licenses and send notifications
     */
    public function checkExpiringLicenses($days = 30)
    {
        try {
            $expiringLicenses = License::where('status', true)
                ->whereNotNull('expiration_date')
                ->where('expiration_date', '>', now())
                ->where('expiration_date', '<=', now()->addDays($days))
                ->orderBy('expiration_date')
                ->get();

            if ($expiringLicenses->isEmpty()) {
                Log::info('No expiring licenses found for notification.');

                return 0;
            }

            // Get admin users to notify
            $adminUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['fad-approver']);
            })->where('status', true)->get();

            if ($adminUsers->isEmpty()) {
                Log::warning('No admin users found to send license expiration notifications.');

                return 0;
            }

            $sentCount = 0;
            foreach ($adminUsers as $admin) {
                try {
                    Mail::to($admin->email)->send(
                        new LicenseExpirationAlert($expiringLicenses, $this->getLicenseSubject($expiringLicenses), $this->getLicenseMessage($expiringLicenses), $days)
                    );
                    $sentCount++;
                    Log::info("License expiration alert sent to: {$admin->email}");
                } catch (\Exception $e) {
                    Log::error("Failed to send license expiration email to {$admin->email}: ".$e->getMessage());
                }
            }

            Log::info("License expiration notifications sent: {$sentCount} emails to {$adminUsers->count()} admins for {$expiringLicenses->count()} licenses.");

            return $sentCount;

        } catch (\Exception $e) {
            Log::error('Error in checkExpiringLicenses: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Check for low stock items and send notifications
     */
    public function checkLowStock()
    {
        try {
            $lowStockItems = StationaryItem::where('status', true)
                ->where('current_stock', '<=', \DB::raw('min_stock'))
                ->orderBy('current_stock')
                ->get();

            if ($lowStockItems->isEmpty()) {
                Log::info('No low stock items found for notification.');

                return 0;
            }

            // Get admin users to notify
            $adminUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['fad-approver']);
            })->where('status', true)->get();

            if ($adminUsers->isEmpty()) {
                Log::warning('No admin users found to send low stock notifications.');

                return 0;
            }

            $sentCount = 0;
            foreach ($adminUsers as $admin) {
                try {
                    Mail::to($admin->email)->send(
                        new LowStockAlert($lowStockItems, $this->getStockSubject($lowStockItems), $this->getStockMessage($lowStockItems))
                    );
                    $sentCount++;
                    Log::info("Low stock alert sent to: {$admin->email}");
                } catch (\Exception $e) {
                    Log::error("Failed to send low stock email to {$admin->email}: ".$e->getMessage());
                }
            }

            Log::info("Low stock notifications sent: {$sentCount} emails to {$adminUsers->count()} admins for {$lowStockItems->count()} items.");

            return $sentCount;

        } catch (\Exception $e) {
            Log::error('Error in checkLowStock: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Generate dynamic subject for license emails
     */
    private function getLicenseSubject($licenses)
    {
        $criticalCount = $licenses->filter(function ($license) {
            return $license->expiration_date->diffInDays(now()) <= 7;
        })->count();

        if ($criticalCount > 0) {
            return "🚨 URGENT: {$criticalCount} License(s) Expiring in 7 Days or Less";
        }

        return "⚠️ Alert: {$licenses->count()} License(s) Expiring Soon";
    }

    /**
     * Generate dynamic message for license emails
     */
    private function getLicenseMessage($licenses)
    {
        $criticalCount = $licenses->filter(function ($license) {
            return $license->expiration_date->diffInDays(now()) <= 7;
        })->count();

        if ($criticalCount > 0) {
            return "{$criticalCount} license(s) are expiring within 7 days and require immediate attention.";
        }

        return "{$licenses->count()} license(s) are expiring soon. Please review and take appropriate action.";
    }

    /**
     * Generate dynamic subject for stock emails
     */
    private function getStockSubject($items)
    {
        $outOfStockCount = $items->where('current_stock', 0)->count();

        if ($outOfStockCount > 0) {
            return "🚨 URGENT: {$outOfStockCount} Item(s) Out of Stock";
        }

        return "⚠️ Alert: {$items->count()} Item(s) Running Low on Stock";
    }

    /**
     * Generate dynamic message for stock emails
     */
    private function getStockMessage($items)
    {
        $outOfStockCount = $items->where('current_stock', 0)->count();
        $lowStockCount = $items->where('current_stock', '>', 0)->count();

        $message = '';
        if ($outOfStockCount > 0) {
            $message .= "{$outOfStockCount} item(s) are completely out of stock. ";
        }
        if ($lowStockCount > 0) {
            $message .= "{$lowStockCount} item(s) are below minimum stock levels.";
        }

        return $message ?: 'Some items require restocking attention.';
    }
}
