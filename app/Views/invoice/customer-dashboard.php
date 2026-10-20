<?php $this->layout = 'layouts/customer';
$ok = $session->getFlash('success');
$err = $session->getFlash('error');
$newKey = $session->get('invoice_new_api_key');
if ($newKey) $session->remove('invoice_new_api_key');

 ?>


        <header class="top">
            <div>
                <h1>Business dashboard</h1>
                <div class="muted">Welcome, <?= esc($customer['name']) ?> · <?= esc($customer['workspace_name']) ?></div>
            </div><a class="btn" href="#create-invoice">+ New invoice</a>
        </header><?php if ($ok): ?><div class="alert success"><?= esc(strip_tags((string)$ok)) ?></div><?php endif; ?><?php if ($err): ?><div class="alert error"><?= esc(strip_tags((string)$err)) ?></div><?php endif; ?><?php if ($newKey): ?><section class="note"><strong>Your new API key — copy it now. It will not be shown again.</strong>
                <p class="key"><?= esc($newKey) ?></p><button type="button" onclick="navigator.clipboard.writeText(<?= json_encode($newKey) ?>).then(()=>this.textContent='Copied')">Copy key</button>
            </section><?php endif; ?><section class="cards">
            <div class="card">
                <div class="muted">Total invoices</div>
                <div class="num"><?= number_format((int)($stats['total'] ?? 0)) ?></div>
            </div>
            <div class="card">
                <div class="muted">Paid invoices</div>
                <div class="num"><?= number_format((int)($stats['paid_count'] ?? 0)) ?></div>
            </div>
            <div class="card">
                <div class="muted">Awaiting payment</div>
                <div class="num"><?= number_format((int)($stats['unpaid_count'] ?? 0)) ?></div>
            </div>
            <div class="card">
                <div class="muted">Paid invoice volume</div>
                <div class="num" style="font-size:22px"><?= esc($customer['default_currency']) ?> <?= number_format((float)($stats['paid_volume'] ?? 0), 2) ?></div>
            </div>
        </section>
        <section class="panel" id="create-invoice">
            <div class="sectionhead">
                <h2>Create an invoice</h2><span class="muted">Hosted invoice link</span>
            </div>
            <form method="post" action="/customer/invoices"><input type="hidden" name="_token" value="<?= esc($token) ?>">
                <div class="formgrid">
                    <div class="field"><label>Customer name</label><input name="customer_name" required maxlength="160"></div>
                    <div class="field"><label>Customer email</label><input type="email" name="customer_email" required></div>
                    <div class="field full"><label>Item / service description</label><input name="description" required maxlength="255"></div>
                    <div class="field"><label>Quantity</label><input type="number" name="quantity" min="0.001" step="0.001" value="1" required></div>
                    <div class="field"><label>Unit price</label><input type="number" name="unit_price" min="0" step="0.01" required></div>
                    <div class="field"><label>Currency</label><select name="currency">
                            <option value="NGN">NGN — Nigerian Naira</option>
                            <option value="USD">USD — US Dollar</option>
                            <option value="GHS">GHS — Ghanaian Cedi</option>
                            <option value="KES">KES — Kenyan Shilling</option>
                            <option value="GBP">GBP — Pound Sterling</option>
                        </select></div>
                    <div class="field"><label>Transaction fee payer</label><select name="fee_payer">
                            <option value="customer">Customer pays</option>
                            <option value="merchant">My business pays</option>
                            <option value="split">Split between both</option>
                        </select></div>
                    <div class="field"><label>Tax (%)</label><input type="number" name="tax_rate" min="0" max="100" step="0.001" value="0"></div>
                    <div class="field"><label>Discount (amount)</label><input type="number" name="discount" min="0" step="0.01" value="0"></div>
                    <div class="field"><label>Due date</label><input type="date" name="due_date"></div>
                    <div class="field full"><button type="submit">Create invoice</button></div>
                </div>
            </form>
        </section>
        <section class="panel">
            <div class="sectionhead">
                <h2>Recent invoices</h2><a href="/customer/invoices" class="btn light">View all</a>
            </div>
            <div class="tablewrap">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Fee payer</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Link</th>
                        </tr>
                    </thead>
                    <tbody><?php if (!$invoices): ?><tr>
                                <td colspan="7" class="muted">No invoices yet. Create your first invoice above.</td>
                            </tr><?php endif;
                                foreach ($invoices as $i): ?><tr>
                                <td><strong><?= esc($i['invoice_number']) ?></strong></td>
                                <td><?= esc($i['customer_name']) ?></td>
                                <td><?= esc($i['currency']) ?> <?= number_format((float)$i['total_amount'], 2) ?></td>
                                <td><?= esc(ucfirst($i['fee_payer'])) ?></td>
                                <td><span class="pill <?= in_array($i['status'], ['cancelled', 'expired', 'refunded']) ? 'bad' : '' ?>"><?= esc($i['status']) ?></span></td>
                                <td><?= esc(substr((string)$i['created_at'], 0, 10)) ?></td>
                                <td><a href="<?= esc($i['hosted_url'] ?: ('/pay/' . $i['public_id'])) ?>" target="_blank" rel="noopener">Open invoice</a></td>
                            </tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </section>
        <section class="panel" id="api-keys">
            <div class="sectionhead">
                <h2>API keys</h2><span class="muted">Keep live keys secret</span>
            </div>
            <form class="keyform" method="post" action="/customer/api-keys"><input type="hidden" name="_token" value="<?= esc($token) ?>"><input name="label" placeholder="Key label (e.g. Production API)" maxlength="100"><select name="mode">
                    <option value="test">Test mode</option>
                    <option value="live">Live mode</option>
                </select><button type="submit">Generate key</button></form>
            <div class="tablewrap" style="margin-top:12px">
                <table>
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Mode</th>
                            <th>Key prefix</th>
                            <th>Last used</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody><?php if (!$keys): ?><tr>
                                <td colspan="6" class="muted">No API keys yet.</td>
                            </tr><?php endif;
                                foreach ($keys as $k): ?><tr>
                                <td><?= esc($k['label']) ?></td>
                                <td><?= esc($k['mode']) ?></td>
                                <td class="key"><?= esc($k['key_prefix']) ?>…</td>
                                <td><?= esc($k['last_used_at'] ?: 'Never') ?></td>
                                <td><span class="pill <?= $k['revoked_at'] ? 'bad' : '' ?>"><?= $k['revoked_at'] ? 'Revoked' : 'Active' ?></span></td>
                                <td><?php if (!$k['revoked_at']): ?><form method="post" action="/customer/api-keys/<?= (int)$k['id'] ?>/revoke" onsubmit="return confirm('Revoke this API key? Integrations using it will stop working.')"><input type="hidden" name="_token" value="<?= $esc($token) ?>"><button type="submit" style="background:#9b3d2e">Revoke</button></form><?php endif; ?></td>
                            </tr><?php endforeach; ?></tbody>
                </table>
            </div>
            <p class="muted" style="margin-top:12px">API endpoint: <code>/api/v1/billing/invoices</code> · Send your key in the <code>X-API-Key</code> header. Never expose keys in frontend JavaScript.</p>
        </section>
    