<?php

use App\Models\adminactivity;

if (!function_exists('logAdminActivity')) {
    /**
     * Logs an admin activity to the database.
     *
     * @param int $relatedTableId 
     * @param int $adminId
     * @param string $description
     * @param string $type
     * @return void
     */
    function logAdminActivity($relatedTableId, $adminId, $description, $type)
    {
        adminactivity::create([
            'retated_table_id' => $relatedTableId,
            'admin_id' => $adminId,
            'description' => $description,
            'type' => $type
        ]);
    }
}
