package uk.me.rsw.blueline;

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager.NameNotFoundException;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.util.Log;
import android.view.KeyEvent;
import android.view.View;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Toast;
import android.widget.ViewSwitcher;

public class BluelineActivity extends Activity {
	protected WebView BluelineWebView;
	protected ViewSwitcher BluelineViewSwitcher;
	protected Handler BluelineHandler;
	
    /** Called when the activity is first created. */
    @Override
    public void onCreate( final Bundle savedInstanceState ) {
        super.onCreate( savedInstanceState );
        setContentView( R.layout.main );
        
        BluelineWebView = (WebView) findViewById( R.id.webview );
        BluelineViewSwitcher = (ViewSwitcher) findViewById( R.id.viewSwitcher );
        BluelineHandler = new Handler();

        WebSettings webSettings = BluelineWebView.getSettings();
        webSettings.setJavaScriptEnabled( true );
        webSettings.setAppCacheMaxSize( 524288 );
        webSettings.setAppCachePath( "/data/data/uk.me.rsw.blueline/cache" );
        webSettings.setAllowFileAccess( true );
        webSettings.setAppCacheEnabled( true );
        webSettings.setCacheMode( WebSettings.LOAD_DEFAULT );
        webSettings.setDatabasePath( "/data/data/uk.me.rsw.blueline/cache" );
        webSettings.setDatabaseEnabled( true );
        webSettings.setDomStorageEnabled( true );
        webSettings.setSupportZoom( true );
        webSettings.setSaveFormData( false );
        try {
			webSettings.setUserAgentString( getString( R.string.app_name )+" "+getPackageManager().getPackageInfo(getPackageName(), 0).versionName+" "+webSettings.getUserAgentString() );
		} catch (NameNotFoundException e) {
			Log.e("tag", e.getMessage());
		}
        
        BluelineWebView.setWebViewClient( new BluelineWebViewClient() );
        BluelineWebView.addJavascriptInterface( new JavaScriptInterface( this ), "Android");
        BluelineWebView.setScrollBarStyle( WebView.SCROLLBARS_INSIDE_OVERLAY );
        
        if( savedInstanceState != null ) {
        	BluelineWebView.restoreState( savedInstanceState );
        }
        else {
	        BluelineWebView.loadUrl( "https://rsw.me.uk/blueline/" );
        }
    }
    
    /** Web Client */
    private class BluelineWebViewClient extends WebViewClient {
        @Override
        public boolean shouldOverrideUrlLoading( WebView view, String url ) {
            if( Uri.parse( url ).getHost().equals( "rsw.me.uk" ) ) {
                // Don't override local links
                return false;
            }
            Intent intent = new Intent( Intent.ACTION_VIEW, Uri.parse( url ) );
            startActivity( intent );
            return true;
        }
    }
    
    /** Javascript interface */
    private class JavaScriptInterface {
        Context mContext;
    	boolean LoadingHidden = false;
    	
        JavaScriptInterface( Context c ) {
            mContext = c;
        }
        
        /** Show a toast from the web page */
        public void showToast( String toast ) {
            Toast.makeText( mContext, toast, Toast.LENGTH_SHORT ).show();
        }
        
        /** Switch the view to the BluelineWebView */
        public void hideLoading() {
        	if( !LoadingHidden ) {
        		BluelineHandler.post( new Runnable() {
					public void run() {
						BluelineWebView.requestFocus(View.FOCUS_DOWN);
						BluelineViewSwitcher.showNext();
					}
				} );
        		LoadingHidden = true;
        	}
        }
    }
    
    /** Handle orientation changes */
    @Override
    protected void onSaveInstanceState(Bundle outState) {
    	BluelineWebView.saveState(outState);
    }
    
    /** Handle back button presses */
    @Override
    public boolean onKeyDown(int keyCode, KeyEvent event) {
        if ((keyCode == KeyEvent.KEYCODE_BACK) && BluelineWebView.canGoBack()) {
        	BluelineWebView.goBack();
            return true;
        }
        return super.onKeyDown(keyCode, event);
    }
}
