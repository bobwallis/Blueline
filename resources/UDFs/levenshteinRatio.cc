#ifdef STANDARD
#include <string.h>
#ifdef __WIN__
typedef unsigned __int64 ulonglong;
typedef __int64 longlong;
#else
typedef unsigned long long ulonglong;
typedef long long longlong;
#endif /*__WIN__*/
#else
#include <my_global.h>
#include <my_sys.h>
#endif
#include <mysql.h>
#include <m_ctype.h>
#include <m_string.h>

#ifdef HAVE_DLOPEN

extern "C" {
	my_bool levenshteinRatio_init( UDF_INIT *initid, UDF_ARGS *args, char *message );
	void levenshteinRatio_deinit( UDF_INIT *initid );
	longlong levenshteinRatio( UDF_INIT *initid, UDF_ARGS *args, char *is_null, char *error );
}

my_bool levenshteinRatio_init( UDF_INIT *initid, UDF_ARGS *args, char *message ) {
	int *workspace;

	/* Make sure user has provided two arguments */
	if( args->arg_count != 2 ) {
		strcpy( message, "levenshteinRatio() requires two arguments" );
		return 1;
	}
	/* Make sure both arguments are strings */
	if( args->arg_type[0] != STRING_RESULT || args->arg_type[1] != STRING_RESULT ) {
		strcpy( message, "levenshteinRatio() requires two string arguments" );
		return 1;
	}

	/* set the maximum number of digits MySQL should expect as the return
	** value of the levenshteinRatio() function */
	initid->max_length = 3;

	/* levenshteinRatio() will not be returning null */
	initid->maybe_null = 0;

	/* attempt to allocate memory in which to calculate levenshtein distance */
	workspace = new int[(args->lengths[0] + 1) * (args->lengths[1] + 1)];
		
	if( workspace == NULL ) {
		strcpy( message, "Failed to allocate memory for levenshteinRatio function" );
		return 1;
	}

	/* initid->ptr is a char* which MySQL provides to share allocated memory
	** among the xxx_init(), xxx_deinit(), and xxx() functions */
	initid->ptr = (char*) workspace;

	return 0;
}

void levenshteinRatio_deinit( UDF_INIT *initid ) {
	if( initid->ptr != NULL ) {
		delete [] initid->ptr;
	}
}

longlong levenshteinRatio( UDF_INIT *initid, UDF_ARGS *args, char *is_null, char *error ) {
	const char *s = args->args[0];
	const char *t = args->args[1];
	int *d = (int*) initid->ptr;

	longlong n, m;
	int b, c, f, g, h, i, j, k, min;
	
	n = (s == NULL)? 0 : args->lengths[0];
	m = (t == NULL)? 0 : args->lengths[1];
	
	if( n == 0 || m == 0 ) {
		return 0;
	}
	else {
		n++;
		m++;

		/* initialize first row to 0..n */
		for( k = 0; k < n; k++ ) {
			d[k] = k;
		}
		/* initialize first column to 0..m */
		for( k = 0; k < m; k++ ) {
      	d[k * n] = k;
		}

		/* g will be equal to i minus one */
		g = 0;
		for( i = 1; i < n; i++) {
			k = i;
			/* f will equal j minus one */
			f = 0;
			for( j = 1; j < m; j++ ) {
				h = k;
				k += n;
				min = d[h] + 1;
				b = d[k-1] + 1;
				c = d[h-1] + (s[g] == t[f] ? 0 : 1);
				if( b < min ) { min = b; }
				if( c < min) { min = c; }
				d[k] = min;
				f = j;
			}
			g = i;
		}
		
		return floor( 0.5 + ( (1-((float)d[k]/max(n,m))) * 100) );
	}
}

#endif /* HAVE_DLOPEN */
