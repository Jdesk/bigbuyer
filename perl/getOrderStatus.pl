use WWW::Mechanize;
use DBI;
use JSON;

#my $dbh = DBI->connect('DBI:mysql:gontha5_main;host=199.250.215.251', 'gontha5_main', 'q%0XahB*K8rV*yb') || die "Could not connect to database: $DBI::errstr";
#$dbh->{mysql_auto_reconnect} = 1;

use WWW::Mechanize;
my $mech = WWW::Mechanize->new('timeout'=>60);
#$mech->agent( 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_4; en-us) AppleWebKit/533.17.8 (KHTML, like Gecko) Version/5.0.1 Safari/533.17.8' );

my $vars = &parseInput();
#$res = &getProxyIP();
#t($res);

$res = &load('https://www.bestbuy.com/profile/ss/orders/order-details?orderId='.$vars->{'orderId'},1,['orderNumber'=> $vars->{'orderId'},'lastName'=>$vars->{'lname'},'phoneNumber'=>$vars->{'phone'}]);
load('https://www.bestbuy.com/profile/ss/order-api/orders/'.$vars->{'orderId'});

print $mech->content;
exit;

sub getProxyIP(){	
	my $ip = '';
	$ip = &load('https://api.ipify.org/?format=text');			
	return $ip;
}
sub load(){
	my $url = shift @_;	
	my $post = shift @_;
	my $post_data = shift @_;
	my $save_to_file = shift @_;	
	my $tries = 0;
	my $browse;	
	my $err = '';
	
	$browser = $mech;
		
	if(!$load_retries){ $load_retries = 5; }	
	if($vars->{'proxy'}){		
		$browser->proxy(['http', 'https'], 'http://'.$vars->{'proxy'});		
	}	
		
	
	#&r("Loading: $url");	
	if(!$url){ &r("Error loading URL: No URL specified.",1,1); }		   		
	do{		
		if($post){				
			eval{$browser->post($url,$post_data);};
			$err = $@;
		}
		else{													
			eval{$browser->get($url);};			
			$err = $@;											
		}
		$tries++;
		#&t("Try #".$tries.": ".$err,1);
	}while($err && $tries<=$load_retries);
	if($err){ return ''; }
	
	if($save_to_file){
		$browser->save_content($save_to_file);
	}	
	
	$res = $browser->content;			
	return $res;	
}
sub parseInput(){	
	my $input = $ARGV[0];
	return decode_json $input;
	
	my @pairs = split(/&/, shift @ARGV);
	foreach my $pair (@pairs) {
		  my ($name, $value) = split(/=/, $pair, 2);		  		  		 
		  $value =~ tr/+/ /;
		  $value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
		  $value =~ s/<!--(.|\n)*-->//g;		
		  $vars->{$name} = $value;
	}
}
sub t(){
	my $v = $_[0];
	my $live = $_[1];
	my $type = ref($v);		
	
	#print "File: ", __FILE__, " Line: ", __LINE__, "\n";
					
	if($type =~ /Mechanize/i){ $file = '/home/buying28/public_html/perl/test.html'; open(out,">$file"); print out $mech->content; close($out); print STDERR "MECH CONTENT PRINTED, $file";}
	elsif($type =~ /HASH|html::element/i){ printHash($v); }	
	else{ print STDERR $v; }
	print STDERR "\n";
	if(!$live){
		exit;
	}
}
sub printHash(){
	my $hash = shift @_;
	my $level = shift @_;
	my $parent = shift @_;
	$level+=0;
	
	if($parent){foreach(1..($level-1)){ print "\t"; } print STDERR "$parent:\n";}
	while ( my ($key, $value) = each(%$hash) ) {
		if(ref($value) =~ /hash/i){ printHash($value,$level+1,$key); }
        else{ 			 
			foreach(1..$level){ print STDERR "\t"; }
			print STDERR "$key => $value\n"; 
		}
    }
}