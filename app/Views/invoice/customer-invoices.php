<?php $this->layout = 'layouts/customer';
$ok = $session->getFlash('success');
$err = $session->getFlash('error'); ?>

        <header class="top">
            <div><a href="/customer/dashboard" class="brand" style="text-decoration:none;color:inherit">Naira<span>Bill</span></a>
                <div>Invoices · <?= esc($customer['workspace_name']) ?></div>
            </div>
            <div><a class="btn" href="/customer/dashboard#create-invoice">Create invoice</a> </div>
        </header>
        <?php if ($ok): ?><div class="alert success"><?= esc(strip_tags((string)$ok)) ?></div><?php endif; ?><?php if ($err): ?><div class="alert error"><?= esc(strip_tags((string)$err)) ?></div><?php endif; ?><section class="panel">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Fee payer</th>
                        <th>Status</th>
                        <th>Due</th>
                        <th>Hosted link</th>
                    </tr>
                </thead>
                <tbody><?php if (!$invoices): ?><tr>
                            <td colspan="8">No invoices found.</td>
                        </tr><?php endif;
                            foreach ($invoices as $i): ?><tr>
                            <td><?= esc($i['invoice_number']) ?></td>
                            <td><?= esc($i['customer_name']) ?></td>
                            <td><?= esc($i['customer_email']) ?></td>
                            <td><?= esc($i['currency']) ?> <?= number_format((float)$i['total_amount'], 2) ?></td>
                            <td><?= esc($i['fee_payer']) ?></td>
                            <td><span class="pill <?= in_array($i['status'], ['cancelled', 'expired', 'refunded']) ? 'bad' : '' ?>"><?= esc($i['status']) ?></span></td>
                            <td><?= esc($i['due_date'] ?: '—') ?></td>
                            <td><a target="_blank" rel="noopener" href="<?= esc($i['hosted_url'] ?: ('/pay/' . $i['public_id'])) ?>">Open</a></td>
                        </tr><?php endforeach; ?></tbody>
            </table>
        </section>
    </main>
</body>

</html>