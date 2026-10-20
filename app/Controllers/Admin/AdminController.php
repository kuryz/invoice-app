<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
// use App\Services\AdminDashboardService;
class AdminController extends Controller
{
    public function dashboard(Request $request, Response $response)
    {
        // $service = new AdminDashboardService();
        $admin = $request->param('admin');
        $geneal = gs();
// print_r($service->transactionReport()['plusTrx'][0]);die;
        return $this->render(
            'admin/dashboard',
            [
                'pageTitle'   => 'Admin Dashboard',
                'admin'       => $admin,
                'general'     => $geneal,
                // 'widget'      => $service->widgets(),
                // 'chart'       => $service->browserCharts(),
                // 'deposit'     => $service->depositStats(),
                // 'withdrawals' => $service->withdrawalStats(),
                // 'trxReport'   => $service->transactionReport(),
                // 'report'      => $service->monthlyReport(),
            ]
        );
    }
}