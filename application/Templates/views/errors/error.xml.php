<?php
namespace Blueline;

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
?>
<error code="<?php echo $errorCode; ?>" title="<?php echo $errorTitle; ?>"><?php echo isset( $errorMessage )? $errorMessage : ''; ?></error>
