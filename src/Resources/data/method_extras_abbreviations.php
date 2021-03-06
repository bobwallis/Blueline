<?php
// Standard calls and rule offs for various methods which aren't in line with what would get generated by default
$method_extras_abbreviations = array(
    // 147 Treble Dodging Minor
    array('title' => 'Abbeyville Delight Minor', 'abbreviation' => 'Av'),
    array('title' => 'Allendale Surprise Minor', 'abbreviation' => 'Ad'),
    array('title' => 'Alnwick Surprise Minor', 'abbreviation' => 'Ak'),
    array('title' => 'Annable\'s London Surprise Minor', 'abbreviation' => 'Ab'),
    array('title' => 'Bacup Surprise Minor', 'abbreviation' => 'Bc'),
    array('title' => 'Balmoral Delight Minor', 'abbreviation' => 'Ba'),
    array('title' => 'Bamborough Surprise Minor', 'abbreviation' => 'Bm'),
    array('title' => 'Barham Delight Minor', 'abbreviation' => 'Bh'),
    array('title' => 'Bedford Delight Minor', 'abbreviation' => 'Be'),
    array('title' => 'Beeston Delight Minor', 'abbreviation' => 'Bt'),
    array('title' => 'Belvoir Delight Minor', 'abbreviation' => 'Bl'),
    array('title' => 'Berkeley Delight Minor', 'abbreviation' => 'By'),
    array('title' => 'Berwick Surprise Minor', 'abbreviation' => 'Bk'),
    array('title' => 'Berwyn Treble Bob Minor', 'abbreviation' => 'Bw'),
    array('title' => 'Beverley Surprise Minor', 'abbreviation' => 'Bv'),
    array('title' => 'Bogedone Delight Minor', 'abbreviation' => 'Bg'),
    array('title' => 'Bourne Surprise Minor', 'abbreviation' => 'Bo'),
    array('title' => 'Braintree Delight Minor', 'abbreviation' => 'Br'),
    array('title' => 'British Scholars\' Pleasure Treble Bob Minor', 'abbreviation' => 'Bp'),
    array('title' => 'Bucknall Delight Minor', 'abbreviation' => 'Bn'),
    array('title' => 'Burnaby Delight Minor', 'abbreviation' => 'Bu'),
    array('title' => 'Burslem Delight Minor', 'abbreviation' => 'Bs'),
    array('title' => 'Burton Treble Bob Minor', 'abbreviation' => 'Bz'),
    array('title' => 'Caernarvon Delight Minor', 'abbreviation' => 'Cz'),
    array('title' => 'Cambridge Surprise Minor', 'abbreviation' => 'Cm'),
    array('title' => 'Cambridge Delight Minor', 'abbreviation' => 'Cg'),
    array('title' => 'Canterbury Surprise Minor', 'abbreviation' => 'Ct'),
    array('title' => 'Canterbury Delight Minor', 'abbreviation' => 'Ca'),
    array('title' => 'Capel Treble Bob Minor', 'abbreviation' => 'Ci'),
    array('title' => 'Carisbrooke Delight Minor', 'abbreviation' => 'Ck'),
    array('title' => 'Carlisle Surprise Minor', 'abbreviation' => 'Cl'),
    array('title' => 'Castleton Delight Minor', 'abbreviation' => 'Cx'),
    array('title' => 'Chadkirk Treble Bob Minor', 'abbreviation' => 'Cj'),
    array('title' => 'Charlwood Delight Minor', 'abbreviation' => 'Cw'),
    array('title' => 'Chelsea Delight Minor', 'abbreviation' => 'Cc'),
    array('title' => 'Chepstow Delight Minor', 'abbreviation' => 'Cs'),
    array('title' => 'Chester Surprise Minor', 'abbreviation' => 'Ch'),
    array('title' => 'Cheviot Treble Bob Minor', 'abbreviation' => 'C3'),
    array('title' => 'Chiltern Treble Bob Minor', 'abbreviation' => 'C2'),
    array('title' => 'Clarence Delight Minor', 'abbreviation' => 'Cr'),
    array('title' => 'Coldstream Surprise Minor', 'abbreviation' => 'Co'),
    array('title' => 'College Bob IV Delight Minor', 'abbreviation' => 'Cb'),
    array('title' => 'College Exercise Treble Bob Minor', 'abbreviation' => 'Cf'),
    array('title' => 'Combermere Delight Minor', 'abbreviation' => 'Ce'),
    array('title' => 'Conisborough Delight Minor', 'abbreviation' => 'Cn'),
    array('title' => 'Conway Delight Minor', 'abbreviation' => 'Cy'),
    array('title' => 'Cotswold Treble Bob Minor', 'abbreviation' => 'C1'),
    array('title' => 'Coventry Delight Minor', 'abbreviation' => 'Cv'),
    array('title' => 'Crowland Delight Minor', 'abbreviation' => 'Cd'),
    array('title' => 'Cunecastre Surprise Minor', 'abbreviation' => 'Cu'),
    array('title' => 'Disley Delight Minor', 'abbreviation' => 'Di'),
    array('title' => 'Donottar Delight Minor', 'abbreviation' => 'Dt'),
    array('title' => 'Dover Delight Minor', 'abbreviation' => 'Do'),
    array('title' => 'Duke of Norfolk Treble Bob Minor', 'abbreviation' => 'Dk'),
    array('title' => 'Dunedin Delight Minor', 'abbreviation' => 'Dn'),
    array('title' => 'Durham Surprise Minor', 'abbreviation' => 'Du'),
    array('title' => 'Edinburgh Delight Minor', 'abbreviation' => 'Ed'),
    array('title' => 'Elston Delight Minor', 'abbreviation' => 'El'),
    array('title' => 'Ely Delight Minor', 'abbreviation' => 'Ey'),
    array('title' => 'Evening Star Delight Minor', 'abbreviation' => 'Es'),
    array('title' => 'Evesham Delight Minor', 'abbreviation' => 'Ev'),
    array('title' => 'Fotheringay Delight Minor', 'abbreviation' => 'Fg'),
    array('title' => 'Fountains Delight Minor', 'abbreviation' => 'Fo'),
    array('title' => 'Francis Genius Delight Minor', 'abbreviation' => 'Fr'),
    array('title' => 'Glastonbury Delight Minor', 'abbreviation' => 'Gl'),
    array('title' => 'Hexham Surprise Minor', 'abbreviation' => 'He'),
    array('title' => 'Hull Surprise Minor', 'abbreviation' => 'Hu'),
    array('title' => 'Humber Delight Minor', 'abbreviation' => 'Hm'),
    array('title' => 'Ipswich Surprise Minor', 'abbreviation' => 'Ip'),
    array('title' => 'Kelso Surprise Minor', 'abbreviation' => 'Ke'),
    array('title' => 'Kent Treble Bob Minor', 'abbreviation' => 'Kt'),
    array('title' => 'Kentish Delight Minor', 'abbreviation' => 'Kh'),
    array('title' => 'Killamarsh Treble Bob Minor', 'abbreviation' => 'Km'),
    array('title' => 'Kingston Treble Bob Minor', 'abbreviation' => 'Ks'),
    array('title' => 'Kirkstall Delight Minor', 'abbreviation' => 'Ki'),
    array('title' => 'Knutsford Delight Minor', 'abbreviation' => 'Kn'),
    array('title' => 'Leasowe Delight Minor', 'abbreviation' => 'Le'),
    array('title' => 'Lightfoot Surprise Minor', 'abbreviation' => 'Lf'),
    array('title' => 'Lincoln Surprise Minor', 'abbreviation' => 'Li'),
    array('title' => 'London Surprise Minor', 'abbreviation' => 'Lo'),
    array('title' => 'London Scholars\' Pleasure Treble Bob Minor', 'abbreviation' => 'Ls'),
    array('title' => 'London Victory Delight Minor', 'abbreviation' => 'Lv'),
    array('title' => 'Ludlow Delight Minor', 'abbreviation' => 'Lu'),
    array('title' => 'Marple Delight Minor', 'abbreviation' => 'Ma'),
    array('title' => 'Melandra Delight Minor', 'abbreviation' => 'Md'),
    array('title' => 'Melrose Delight Minor', 'abbreviation' => 'Ml'),
    array('title' => 'Mendip Treble Bob Minor', 'abbreviation' => 'Mp'),
    array('title' => 'Merton Delight Minor', 'abbreviation' => 'Me'),
    array('title' => 'Morning Star Treble Bob Minor', 'abbreviation' => 'Ms'),
    array('title' => 'Morpeth Surprise Minor', 'abbreviation' => 'Mo'),
    array('title' => 'Munden Surprise Minor', 'abbreviation' => 'Mu'),
    array('title' => 'Neasden Delight Minor', 'abbreviation' => 'Ns'),
    array('title' => 'Nelson Treble Bob Minor', 'abbreviation' => 'Nl'),
    array('title' => 'Netherseale Surprise Minor', 'abbreviation' => 'Ne'),
    array('title' => 'Newcastle Surprise Minor', 'abbreviation' => 'Nw'),
    array('title' => 'Newdigate Delight Minor', 'abbreviation' => 'Ng'),
    array('title' => 'Norbury Treble Bob Minor', 'abbreviation' => 'Ny'),
    array('title' => 'Norfolk Surprise Minor', 'abbreviation' => 'Nf'),
    array('title' => 'Northumberland Surprise Minor', 'abbreviation' => 'Nb'),
    array('title' => 'Norton le Moors Treble Bob Minor', 'abbreviation' => 'Nm'),
    array('title' => 'Norwich Surprise Minor', 'abbreviation' => 'No'),
    array('title' => 'Ockley Treble Bob Minor', 'abbreviation' => 'Oc'),
    array('title' => 'Old Oxford Delight Minor', 'abbreviation' => 'Ol'),
    array('title' => 'Oswald Delight Minor', 'abbreviation' => 'Os'),
    array('title' => 'Oxford Treble Bob Minor', 'abbreviation' => 'Ox'),
    array('title' => 'Pembroke Delight Minor', 'abbreviation' => 'Pm'),
    array('title' => 'Pennine Treble Bob Minor', 'abbreviation' => 'Pn'),
    array('title' => 'Pevensey Delight Minor', 'abbreviation' => 'Pe'),
    array('title' => 'Peveril Delight Minor', 'abbreviation' => 'Pv'),
    array('title' => 'Pontefract Delight Minor', 'abbreviation' => 'Po'),
    array('title' => 'Primrose Surprise Minor', 'abbreviation' => 'Pr'),
    array('title' => 'Quantock Treble Bob Minor', 'abbreviation' => 'Qu'),
    array('title' => 'Richborough Delight Minor', 'abbreviation' => 'Ri'),
    array('title' => 'Rochester Treble Bob Minor', 'abbreviation' => 'Rc'),
    array('title' => 'Rossendale Surprise Minor', 'abbreviation' => 'Ro'),
    array('title' => 'Rostherne Delight Minor', 'abbreviation' => 'Rs'),
    array('title' => 'Sandal Treble Bob Minor', 'abbreviation' => 'Sd'),
    array('title' => 'Sandiacre Surprise Minor', 'abbreviation' => 'Sa'),
    array('title' => 'Sherborne Delight Minor', 'abbreviation' => 'Sh'),
    array('title' => 'Skipton Delight Minor', 'abbreviation' => 'Sk'),
    array('title' => 'Snowdon Treble Bob Minor', 'abbreviation' => 'Sn'),
    array('title' => 'Southwark Delight Minor', 'abbreviation' => 'So'),
    array('title' => 'St Albans Delight Minor', 'abbreviation' => 'Sl'),
    array('title' => 'Stamford Surprise Minor', 'abbreviation' => 'St'),
    array('title' => 'Stirling Delight Minor', 'abbreviation' => 'Sg'),
    array('title' => 'St Werburgh Delight Minor', 'abbreviation' => 'Sw'),
    array('title' => 'Surfleet Surprise Minor', 'abbreviation' => 'Su'),
    array('title' => 'Taxal Delight Minor', 'abbreviation' => 'Ta'),
    array('title' => 'Tewkesbury Delight Minor', 'abbreviation' => 'Te'),
    array('title' => 'Tintern Delight Minor', 'abbreviation' => 'Ti'),
    array('title' => 'Trinity Sunday Treble Bob Minor', 'abbreviation' => 'Tr'),
    array('title' => 'Vale Royal Delight Minor', 'abbreviation' => 'Va'),
    array('title' => 'Waltham Delight Minor', 'abbreviation' => 'Wa'),
    array('title' => 'Warkworth Surprise Minor', 'abbreviation' => 'Wk'),
    array('title' => 'Warwick Delight Minor', 'abbreviation' => 'Ww'),
    array('title' => 'Waterford Treble Bob Minor', 'abbreviation' => 'Wf'),
    array('title' => 'Wath Delight Minor', 'abbreviation' => 'Wt'),
    array('title' => 'Wearmouth Surprise Minor', 'abbreviation' => 'Wm'),
    array('title' => 'Wells Surprise Minor', 'abbreviation' => 'We'),
    array('title' => 'Westminster Surprise Minor', 'abbreviation' => 'Ws'),
    array('title' => 'Whitley Surprise Minor', 'abbreviation' => 'Wh'),
    array('title' => 'Willesden Delight Minor', 'abbreviation' => 'Wi'),
    array('title' => 'Wilmslow Delight Minor', 'abbreviation' => 'Wl'),
    array('title' => 'Woodcocks Victory Treble Bob Minor', 'abbreviation' => 'Wv'),
    array('title' => 'Wooler Surprise Minor', 'abbreviation' => 'Wo'),
    array('title' => 'Wragby Delight Minor', 'abbreviation' => 'Wr'),
    array('title' => 'York Surprise Minor', 'abbreviation' => 'Yo'),
    array('title' => 'Blunsdon Surprise Minor', 'abbreviation' => 'Bd'),
    array('title' => 'Brentford Surprise Minor', 'abbreviation' => 'Bf'),
    array('title' => 'Caithness Surprise Minor', 'abbreviation' => 'Cp'),
    array('title' => 'Cranford Surprise Minor', 'abbreviation' => 'Cq'),
    array('title' => 'Flamstead Surprise Minor', 'abbreviation' => 'Fl'),
    array('title' => 'Fryerning Surprise Minor', 'abbreviation' => 'Fy'),
    array('title' => 'Hathern Surprise Minor', 'abbreviation' => 'Ha'),
    array('title' => 'Langleybury Surprise Minor', 'abbreviation' => 'La'),
    array('title' => 'Offley Surprise Minor', 'abbreviation' => 'Of'),
    array('title' => 'Redbourn Surprise Minor', 'abbreviation' => 'Re'),
    array('title' => 'Sedlescombe Surprise Minor', 'abbreviation' => 'Se'),
    array('title' => 'Alderbourne Delight Minor', 'abbreviation' => 'Al'),
    array('title' => 'Cadoxton Delight Minor', 'abbreviation' => 'C4'),
    array('title' => 'Cogenhoe Delight Minor', 'abbreviation' => 'C5'),
    array('title' => 'Danbury Delight Minor', 'abbreviation' => 'Da'),
    array('title' => 'Easthampstead Delight Minor', 'abbreviation' => 'Eh'),
    array('title' => 'Finchampstead Delight Minor', 'abbreviation' => 'Fi'),
    array('title' => 'Fulmer Delight Minor', 'abbreviation' => 'Fu'),
    array('title' => 'Hatherop Delight Minor', 'abbreviation' => 'Hp'),
    array('title' => 'Hertfordshire Delight Minor', 'abbreviation' => 'Hf'),
    array('title' => 'Hitcham Delight Minor', 'abbreviation' => 'Hi'),
    array('title' => 'Middlesex Delight Minor', 'abbreviation' => 'Mi'),
    array('title' => 'Neath Delight Minor', 'abbreviation' => 'Nh'),
    array('title' => 'Pattishall Delight Minor', 'abbreviation' => 'Pa'),
    array('title' => 'Pebmarsh Delight Minor', 'abbreviation' => 'Pb'),
    array('title' => 'Richmond Delight Minor', 'abbreviation' => 'Rm'),
    array('title' => 'Shelford Delight Minor', 'abbreviation' => 'Sf'),
    array('title' => 'Stisted Delight Minor', 'abbreviation' => 'Si'),
    array('title' => 'Tollesbury Delight Minor', 'abbreviation' => 'To'),
    array('title' => 'Westmorland Delight Minor', 'abbreviation' => 'Wd'),
    array('title' => 'Wiggenhall Delight Minor', 'abbreviation' => 'Wg'),
    // Surprise Major
    array('title' => 'Ashtead Surprise Major', 'abbreviation' => 'A'),
    array('title' => 'Bristol Surprise Major', 'abbreviation' => 'B'),
    array('title' => 'Cambridge Surprise Major', 'abbreviation' => 'C'),
    array('title' => 'Cassiobury Surprise Major', 'abbreviation' => 'O'),
    array('title' => 'Lincolnshire Surprise Major', 'abbreviation' => 'N'),
    array('title' => 'London Surprise Major', 'abbreviation' => 'L'),
    array('title' => 'Pudsey Surprise Major', 'abbreviation' => 'P'),
    array('title' => 'Rutland Surprise Major', 'abbreviation' => 'R'),
    array('title' => 'Superlative Surprise Major', 'abbreviation' => 'S'),
    array('title' => 'Yorkshire Surprise Major', 'abbreviation' => 'Y'),
    array('title' => 'Uxbridge Surprise Major', 'abbreviation' => 'U'),
);
