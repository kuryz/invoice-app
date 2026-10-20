<?php
namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

/** Platform-wide operational console. Access is guarded by AdminAuthMiddleware. */
class InvoicePlatformAdminController extends Controller
{
    private PDO $db;
    public function __construct() { parent::__construct(); $this->db = Database::getInstance()->getConnection(); }

    public function dashboard(Request $request, Response $response): string
    {
        $stats = [
            'workspaces' => (int)$this->db->query('SELECT COUNT(*) FROM invoice_workspaces')->fetchColumn(),
            'active_workspaces' => (int)$this->db->query("SELECT COUNT(*) FROM invoice_workspaces WHERE status='active'")->fetchColumn(),
            'invoices' => (int)$this->db->query('SELECT COUNT(*) FROM invoices')->fetchColumn(),
            'unpaid' => (int)$this->db->query("SELECT COUNT(*) FROM invoices WHERE status='unpaid'")->fetchColumn(),
            'paid' => (int)$this->db->query("SELECT COUNT(*) FROM invoices WHERE status='paid'")->fetchColumn(),
            'volume' => (float)$this->db->query("SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE status='paid'")->fetchColumn(),
            'fees' => (float)$this->db->query("SELECT COALESCE(SUM(platform_fee),0) FROM invoices WHERE status='paid'")->fetchColumn(),
            'active_keys' => (int)$this->db->query('SELECT COUNT(*) FROM invoice_api_keys WHERE revoked_at IS NULL')->fetchColumn(),
            'webhooks' => (int)$this->db->query("SELECT COUNT(*) FROM invoice_webhooks WHERE status='active'")->fetchColumn(),
        ];
        $recent = $this->db->query('SELECT i.invoice_number,i.customer_name,i.currency,i.total_amount,i.status,i.created_at,w.name workspace_name FROM invoices i JOIN invoice_workspaces w ON w.id=i.workspace_id ORDER BY i.id DESC LIMIT 12')->fetchAll(PDO::FETCH_ASSOC);
        return $this->render('admin/invoice-platform/dashboard', ['pageTitle'=>'NairaBill Platform Overview','stats'=>$stats,'recent'=>$recent]);
    }

    public function workspaces(Request $request, Response $response): string
    {
        $rows = $this->db->query('SELECT w.*,COUNT(DISTINCT i.id) invoice_count,COUNT(DISTINCT k.id) key_count FROM invoice_workspaces w LEFT JOIN invoices i ON i.workspace_id=w.id LEFT JOIN invoice_api_keys k ON k.workspace_id=w.id GROUP BY w.id ORDER BY w.id DESC LIMIT 500')->fetchAll(PDO::FETCH_ASSOC);
        return $this->render('admin/invoice-platform/workspaces', ['pageTitle'=>'Workspaces','rows'=>$rows]);
    }

    public function invoices(Request $request, Response $response): string
    {
        $rows = $this->db->query('SELECT i.*,w.name workspace_name FROM invoices i JOIN invoice_workspaces w ON w.id=i.workspace_id ORDER BY i.id DESC LIMIT 500')->fetchAll(PDO::FETCH_ASSOC);
        return $this->render('admin/invoice-platform/invoices', ['pageTitle'=>'All Invoices','rows'=>$rows]);
    }

    public function apiKeys(Request $request, Response $response): string
    {
        $rows = $this->db->query('SELECT k.id,k.workspace_id,k.label,k.mode,k.key_prefix,k.last_used_at,k.revoked_at,k.created_at,w.name workspace_name FROM invoice_api_keys k JOIN invoice_workspaces w ON w.id=k.workspace_id ORDER BY k.id DESC LIMIT 500')->fetchAll(PDO::FETCH_ASSOC);
        return $this->render('admin/invoice-platform/api-keys', ['pageTitle'=>'API Keys','rows'=>$rows]);
    }

    public function webhooks(Request $request, Response $response): string
    {
        $rows = $this->db->query('SELECT h.id,h.workspace_id,h.url,h.status,h.created_at,w.name workspace_name FROM invoice_webhooks h JOIN invoice_workspaces w ON w.id=h.workspace_id ORDER BY h.id DESC LIMIT 500')->fetchAll(PDO::FETCH_ASSOC);
        return $this->render('admin/invoice-platform/webhooks', ['pageTitle'=>'Webhooks','rows'=>$rows]);
    }

    public function events(Request $request, Response $response): string
    {
        $rows = $this->db->query('SELECT e.id,e.event_type,e.payload_json,e.created_at,i.invoice_number,w.name workspace_name FROM invoice_events e JOIN invoices i ON i.id=e.invoice_id JOIN invoice_workspaces w ON w.id=i.workspace_id ORDER BY e.id DESC LIMIT 300')->fetchAll(PDO::FETCH_ASSOC);
        return $this->render('admin/invoice-platform/events', ['pageTitle'=>'Invoice Events','rows'=>$rows]);
    }

    public function toggleWorkspace(Request $request, Response $response, string $id): void
    {
        $id = filter_var($id, FILTER_VALIDATE_INT); if (!$id) { http_response_code(400); echo 'Invalid workspace'; return; }
        $s=$this->db->prepare("UPDATE invoice_workspaces SET status=IF(status='active','suspended','active'),updated_at=NOW() WHERE id=?"); $s->execute([$id]);
        $this->redirect('/admin/platform/workspaces');
    }

    public function revokeKey(Request $request, Response $response, string $id): void
    {
        $id = filter_var($id, FILTER_VALIDATE_INT); if (!$id) { http_response_code(400); echo 'Invalid API key'; return; }
        $s=$this->db->prepare('UPDATE invoice_api_keys SET revoked_at=NOW() WHERE id=? AND revoked_at IS NULL'); $s->execute([$id]);
        $this->redirect('/admin/platform/api-keys');
    }
}
