<?php
// Lightweight smoke test for the documented fee allocation arithmetic.
$tests = [
 ['amount'=>10000.00,'rate'=>1.5,'fixed'=>0.0,'payer'=>'customer','fee'=>150.00,'customer'=>150.00,'merchant'=>0.00,'due'=>10150.00],
 ['amount'=>10000.00,'rate'=>1.5,'fixed'=>0.0,'payer'=>'merchant','fee'=>150.00,'customer'=>0.00,'merchant'=>150.00,'due'=>10000.00],
 ['amount'=>10000.00,'rate'=>1.5,'fixed'=>0.0,'payer'=>'split','fee'=>150.00,'customer'=>75.00,'merchant'=>75.00,'due'=>10075.00],
];
foreach ($tests as $t) {
 $fee=round($t['amount']*$t['rate']/100+$t['fixed'],2);
 $customer=$t['payer']==='customer'?$fee:($t['payer']==='split'?round($fee/2,2):0);
 $merchant=round($fee-$customer,2);$due=round($t['amount']+$customer,2);
 if(abs($fee-$t['fee'])>0.001||abs($customer-$t['customer'])>0.001||abs($merchant-$t['merchant'])>0.001||abs($due-$t['due'])>0.001) {fwrite(STDERR,"Fee calculation failed\n");exit(1);}
}
echo "Fee calculation smoke tests passed.\n";
