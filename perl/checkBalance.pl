use WWW::Mechanize;
use JSON;


my $input = $ARGV[0];
my $vars = decode_json $input;


my $mech = WWW::Mechanize->new();
$mech->proxy(['https', 'http', 'ftp'], 'http://'.$vars->{'proxy'});

my $j = encode_json { 'cardNumber' => $vars->{'cardNumber'},'pin' => $vars->{'pin'} };
$mech->get('https://www.bestbuy.com/gift-card-balance');
$mech->post('https://www.bestbuy.com/gift-card-balance/api/lookup','Content-Type' => 'application/json', Content => $j);
print $mech->content;