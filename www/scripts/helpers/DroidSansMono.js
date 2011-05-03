/*global require: false, define: false, google: false */
define( {
	'small': [
		'm0,2.75c-0.6,0,-1.044,-0.244,-1.356,-0.756c-0.311,-0.511,-0.466,-1.244,-0.466,-2.2c0,-1.955,0.622,-2.933,1.822,-2.933c0.6,0,1.044,0.245,1.356,0.756c0.311,0.511,0.466,1.222,0.466,2.177c0,1.956,-0.6,2.956,-1.822,2.956zm0,-0.622c0.378,0,0.667,-0.178,0.844,-0.534c0.178,-0.355,0.267,-0.977,0.267,-1.8c0,-0.822,-0.089,-1.422,-0.267,-1.777c-0.177,-0.356,-0.466,-0.556,-0.844,-0.556c-0.378,0,-0.667,0.2,-0.844,0.556c-0.178,0.355,-0.267,0.955,-0.267,1.777c0,0.823,0.089,1.445,0.267,1.8c0.177,0.356,0.466,0.534,0.844,0.534z',
		'm0.5,2.75l-0.689,0l0,-3.556c0,-0.311,0.022,-0.8,0.045,-1.422c-0.112,0.111,-0.267,0.267,-0.489,0.445l-0.556,0.466l-0.378,-0.466l1.489,-1.178l0.578,0l0,5.711z',
		'm1.75,2.75l-3.556,0l0,-0.6l1.356,-1.489c0.511,-0.555,0.844,-0.978,1,-1.244c0.156,-0.267,0.244,-0.578,0.244,-0.911c0,-0.289,-0.088,-0.512,-0.244,-0.689c-0.156,-0.178,-0.378,-0.267,-0.667,-0.267c-0.422,0,-0.822,0.178,-1.244,0.533l-0.4,-0.444c0.489,-0.445,1.044,-0.689,1.644,-0.689c0.511,0,0.911,0.156,1.2,0.422c0.289,0.267,0.445,0.645,0.445,1.111c0,0.311,-0.067,0.623,-0.222,0.956c-0.156,0.333,-0.556,0.822,-1.156,1.467l-1.089,1.155l0,0.045l2.689,0l0,0.644z',
		'm0.5,-0.25l0,0.022c0.933,0.134,1.4,0.578,1.4,1.356c0,0.533,-0.178,0.933,-0.533,1.244c-0.356,0.311,-0.867,0.467,-1.556,0.467c-0.622,0,-1.133,-0.111,-1.511,-0.311l0,-0.667c0.489,0.245,0.978,0.378,1.489,0.378c0.911,0,1.4,-0.378,1.4,-1.133c0,-0.667,-0.489,-1.023,-1.489,-1.023l-0.533,0l0,-0.577l0.533,0c0.422,0,0.733,-0.089,0.956,-0.289c0.222,-0.2,0.355,-0.467,0.355,-0.8c0,-0.267,-0.089,-0.467,-0.267,-0.623c-0.177,-0.155,-0.422,-0.244,-0.711,-0.244c-0.466,0,-0.933,0.178,-1.355,0.489l-0.356,-0.489c0.489,-0.4,1.067,-0.6,1.711,-0.6c0.534,0,0.934,0.133,1.245,0.4c0.311,0.267,0.466,0.6,0.466,1.022c0,0.356,-0.111,0.667,-0.333,0.911c-0.222,0.245,-0.511,0.4,-0.911,0.467z',
		'm1.9,1.5l-0.844,0l0,1.289l-0.667,0l0,-1.289l-2.645,0l0,-0.622l2.578,-3.822l0.734,0l0,3.8l0.844,0l0,0.644zm-1.511,-0.644l0,-1.378c0,-0.467,0,-1.022,0.044,-1.689l-0.044,0c-0.089,0.267,-0.222,0.489,-0.356,0.689l-1.6,2.378l1.956,0z',
		'm-1.6,2.5l0,-0.689c0.378,0.245,0.867,0.378,1.422,0.378c0.867,0,1.311,-0.4,1.311,-1.222c0,-0.756,-0.466,-1.156,-1.355,-1.156c-0.222,0,-0.511,0.045,-0.889,0.111l-0.356,-0.222l0.2,-2.689l2.711,0l0,0.645l-2.088,0l-0.156,1.644c0.289,-0.044,0.556,-0.089,0.822,-0.089c0.556,0,1,0.156,1.334,0.445c0.333,0.288,0.488,0.733,0.488,1.244c0,0.6,-0.177,1.067,-0.533,1.4c-0.355,0.333,-0.867,0.511,-1.511,0.511c-0.578,0,-1.067,-0.111,-1.4,-0.311z',
		'm1.5,-3l0,0.6c-0.178,-0.067,-0.422,-0.089,-0.667,-0.089c-0.577,0,-1.022,0.156,-1.311,0.533c-0.289,0.378,-0.444,0.956,-0.466,1.756l0.044,0c0.244,-0.444,0.644,-0.667,1.2,-0.667c0.511,0,0.911,0.156,1.2,0.467c0.289,0.311,0.422,0.733,0.422,1.267c0,0.6,-0.155,1.066,-0.466,1.4c-0.312,0.333,-0.734,0.533,-1.267,0.533c-0.578,0,-1.022,-0.222,-1.356,-0.667c-0.333,-0.444,-0.511,-1.066,-0.511,-1.866c0,-2.245,0.822,-3.356,2.489,-3.356c0.267,0,0.511,0.045,0.689,0.089zm-0.267,3.867c0,-0.378,-0.089,-0.667,-0.266,-0.867c-0.178,-0.2,-0.423,-0.311,-0.756,-0.311c-0.333,0,-0.622,0.133,-0.844,0.333c-0.223,0.2,-0.334,0.445,-0.334,0.711c0,0.4,0.111,0.734,0.311,1.023c0.2,0.288,0.512,0.444,0.845,0.444c0.333,0,0.578,-0.133,0.755,-0.356c0.178,-0.222,0.289,-0.555,0.289,-0.977z',
		'm-1,2.75l2.2,-5.067l-2.956,0l0,-0.644l3.667,0l0,0.555l-2.155,5.156l-0.756,0z',
		'm0.6,-0.25c0.8,0.422,1.222,0.933,1.222,1.533c0,0.467,-0.178,0.845,-0.511,1.134c-0.333,0.289,-0.755,0.444,-1.289,0.444c-0.555,0,-1,-0.155,-1.311,-0.422c-0.311,-0.267,-0.489,-0.645,-0.489,-1.133c0,-0.667,0.378,-1.178,1.111,-1.534c-0.622,-0.378,-0.911,-0.866,-0.911,-1.444c0,-0.422,0.134,-0.734,0.445,-0.978c0.311,-0.244,0.689,-0.378,1.155,-0.378c0.489,0,0.867,0.134,1.156,0.378c0.289,0.244,0.444,0.578,0.444,1c0,0.6,-0.333,1.067,-1.022,1.4zm-0.578,-0.289c0.6,-0.267,0.911,-0.622,0.911,-1.089c0,-0.266,-0.089,-0.466,-0.244,-0.6c-0.156,-0.133,-0.378,-0.2,-0.667,-0.2c-0.266,0,-0.511,0.067,-0.666,0.2c-0.156,0.134,-0.245,0.334,-0.245,0.6c0,0.245,0.067,0.445,0.2,0.6c0.133,0.156,0.356,0.334,0.711,0.489zm-0.111,0.6c-0.667,0.311,-0.978,0.733,-0.978,1.267c0,0.622,0.356,0.933,1.067,0.933c0.355,0,0.622,-0.089,0.822,-0.267c0.2,-0.177,0.289,-0.4,0.289,-0.711c0,-0.244,-0.089,-0.444,-0.245,-0.622c-0.155,-0.178,-0.422,-0.356,-0.822,-0.556z',
		'm-1.3,2.75l0,-0.6c0.178,0.067,0.4,0.089,0.667,0.089c0.577,0,1.022,-0.156,1.311,-0.533c0.289,-0.378,0.444,-0.956,0.466,-1.756l-0.044,0c-0.244,0.444,-0.644,0.667,-1.2,0.667c-0.511,0,-0.911,-0.156,-1.2,-0.467c-0.289,-0.311,-0.422,-0.733,-0.422,-1.267c0,-0.6,0.155,-1.066,0.466,-1.4c0.312,-0.333,0.734,-0.533,1.267,-0.533c0.578,0,1.022,0.222,1.356,0.667c0.333,0.444,0.511,1.066,0.511,1.866c0,2.245,-0.822,3.356,-2.489,3.356c-0.267,0,-0.511,-0.045,-0.689,-0.089zm0.267,-3.867c0,0.378,0.089,0.667,0.266,0.867c0.178,0.2,0.423,0.311,0.756,0.311c0.333,0,0.622,-0.111,0.844,-0.333c0.223,-0.222,0.334,-0.445,0.334,-0.711c0,-0.4,-0.111,-0.734,-0.311,-1.023c-0.2,-0.288,-0.489,-0.444,-0.845,-0.444c-0.333,0,-0.6,0.133,-0.778,0.356c-0.177,0.222,-0.266,0.555,-0.266,0.977z'
	],
	'medium': [
		'',
		'm0.6889,3l-0.861,0l0,-4.444c0,-0.389,0.028,-1,0.055,-1.778c-0.138,0.139,-0.333,0.333,-0.611,0.555l-0.694,0.584l-0.472,-0.584l1.861,-1.472l0.722,0l0,7.139z',
		'm2.2167,3.45l-4.444,0l0,-0.75l1.694,-1.861c0.639,-0.695,1.056,-1.222,1.25,-1.556c0.194,-0.333,0.306,-0.722,0.306,-1.139c0,-0.361,-0.112,-0.638,-0.306,-0.861c-0.194,-0.222,-0.472,-0.333,-0.833,-0.333c-0.528,0,-1.028,0.222,-1.556,0.667l-0.5,-0.556c0.611,-0.555,1.306,-0.861,2.056,-0.861c0.639,0,1.139,0.194,1.5,0.528c0.361,0.333,0.555,0.805,0.555,1.389c0,0.389,-0.083,0.777,-0.278,1.194c-0.194,0.417,-0.694,1.028,-1.444,1.833l-1.361,1.445l0,0.055l3.361,0l0,0.806z',
		'm0.3833,-0.45l0,0.028c1.167,0.166,1.75,0.722,1.75,1.694c0,0.667,-0.222,1.167,-0.667,1.556c-0.444,0.389,-1.083,0.583,-1.944,0.583c-0.778,0,-1.417,-0.139,-1.889,-0.389l0,-0.833c0.611,0.305,1.222,0.472,1.861,0.472c1.139,0,1.75,-0.472,1.75,-1.417c0,-0.833,-0.611,-1.277,-1.861,-1.277l-0.667,0l0,-0.723l0.667,0c0.528,0,0.917,-0.111,1.194,-0.361c0.278,-0.25,0.445,-0.583,0.445,-1c0,-0.333,-0.111,-0.583,-0.333,-0.777c-0.223,-0.195,-0.528,-0.306,-0.889,-0.306c-0.584,0,-1.167,0.222,-1.695,0.611l-0.444,-0.611c0.611,-0.5,1.333,-0.75,2.139,-0.75c0.666,0,1.166,0.167,1.555,0.5c0.389,0.333,0.584,0.75,0.584,1.278c0,0.444,-0.139,0.833,-0.417,1.139c-0.278,0.305,-0.639,0.5,-1.139,0.583z',
		'm2.4944,1.75l-1.056,0l0,1.611l-0.833,0l0,-1.611l-3.305,0l0,-0.778l3.222,-4.778l0.916,0l0,4.75l1.056,0l0,0.806zm-1.889,-0.806l0,-1.722c0,-0.583,0,-1.278,0.056,-2.111l-0.056,0c-0.111,0.333,-0.278,0.611,-0.444,0.861l-2,2.972l2.444,0z',
		'm-2.1722,3.05l0,-0.861c0.472,0.305,1.083,0.472,1.778,0.472c1.083,0,1.639,-0.5,1.639,-1.528c0,-0.944,-0.584,-1.444,-1.695,-1.444c-0.278,0,-0.639,0.055,-1.111,0.139l-0.444,-0.278l0.25,-3.361l3.389,0l0,0.805l-2.612,0l-0.194,2.056c0.361,-0.056,0.694,-0.111,1.028,-0.111c0.694,0,1.25,0.194,1.666,0.555c0.417,0.362,0.612,0.917,0.612,1.556c0,0.75,-0.223,1.333,-0.667,1.75c-0.445,0.417,-1.083,0.639,-1.889,0.639c-0.722,0,-1.333,-0.139,-1.75,-0.389z',
		'm1.7167,-3.75l0,0.75c-0.222,-0.083,-0.528,-0.111,-0.833,-0.111c-0.723,0,-1.278,0.194,-1.639,0.667c-0.361,0.472,-0.556,1.194,-0.584,2.194l0.056,0c0.306,-0.556,0.806,-0.833,1.5,-0.833c0.639,0,1.139,0.194,1.5,0.583c0.361,0.389,0.528,0.917,0.528,1.583c0,0.75,-0.195,1.334,-0.584,1.75c-0.388,0.417,-0.916,0.667,-1.583,0.667c-0.722,0,-1.278,-0.278,-1.694,-0.833c-0.417,-0.556,-0.639,-1.334,-0.639,-2.334c0,-2.805,1.028,-4.194,3.111,-4.194c0.333,0,0.639,0.055,0.861,0.111zm-0.333,4.833c0,-0.472,-0.111,-0.833,-0.334,-1.083c-0.222,-0.25,-0.527,-0.389,-0.944,-0.389c-0.417,0,-0.778,0.167,-1.056,0.417c-0.277,0.25,-0.416,0.555,-0.416,0.889c0,0.5,0.139,0.916,0.389,1.277c0.25,0.362,0.638,0.556,1.055,0.556c0.417,0,0.722,-0.167,0.945,-0.444c0.222,-0.278,0.361,-0.695,0.361,-1.223z',
		'm-1.3667,3.45l2.75,-6.333l-3.694,0l0,-0.806l4.583,0l0,0.695l-2.695,6.444l-0.944,0z',
		'm0.7167,-0.35c1,0.528,1.528,1.167,1.528,1.917c0,0.583,-0.222,1.055,-0.639,1.416c-0.417,0.361,-0.945,0.556,-1.611,0.556c-0.695,0,-1.25,-0.195,-1.639,-0.528c-0.389,-0.333,-0.611,-0.805,-0.611,-1.417c0,-0.833,0.472,-1.472,1.389,-1.916c-0.778,-0.472,-1.139,-1.084,-1.139,-1.806c0,-0.528,0.166,-0.916,0.555,-1.222c0.389,-0.306,0.861,-0.472,1.445,-0.472c0.611,0,1.083,0.166,1.444,0.472c0.361,0.306,0.556,0.722,0.556,1.25c0,0.75,-0.417,1.333,-1.278,1.75zm-0.722,-0.361c0.75,-0.333,1.139,-0.778,1.139,-1.361c0,-0.334,-0.111,-0.584,-0.306,-0.75c-0.194,-0.167,-0.472,-0.25,-0.833,-0.25c-0.334,0,-0.639,0.083,-0.834,0.25c-0.194,0.166,-0.305,0.416,-0.305,0.75c0,0.305,0.083,0.555,0.25,0.75c0.167,0.194,0.444,0.416,0.889,0.611zm-0.139,0.75c-0.833,0.389,-1.222,0.917,-1.222,1.583c0,0.778,0.444,1.167,1.333,1.167c0.445,0,0.778,-0.111,1.028,-0.333c0.25,-0.223,0.361,-0.5,0.361,-0.889c0,-0.306,-0.111,-0.556,-0.305,-0.778c-0.195,-0.222,-0.528,-0.444,-1.028,-0.694z',
		'm-1.7278,3.25l0,-0.75c0.222,0.083,0.5,0.111,0.833,0.111c0.723,0,1.278,-0.194,1.639,-0.667c0.361,-0.472,0.556,-1.194,0.584,-2.194l-0.056,0c-0.306,0.556,-0.806,0.833,-1.5,0.833c-0.639,0,-1.139,-0.194,-1.5,-0.583c-0.361,-0.389,-0.528,-0.917,-0.528,-1.583c0,-0.75,0.195,-1.334,0.584,-1.75c0.388,-0.417,0.916,-0.667,1.583,-0.667c0.722,0,1.278,0.278,1.694,0.833c0.417,0.556,0.639,1.334,0.639,2.334c0,2.805,-1.028,4.194,-3.111,4.194c-0.333,0,-0.639,-0.055,-0.861,-0.111zm0.333,-4.833c0,0.472,0.111,0.833,0.334,1.083c0.222,0.25,0.527,0.389,0.944,0.389c0.417,0,0.778,-0.139,1.056,-0.417c0.277,-0.278,0.416,-0.555,0.416,-0.889c0,-0.5,-0.139,-0.916,-0.389,-1.277c-0.25,-0.362,-0.611,-0.556,-1.055,-0.556c-0.417,0,-0.75,0.167,-0.972,0.444c-0.223,0.278,-0.334,0.695,-0.334,1.223z'
	]
} );