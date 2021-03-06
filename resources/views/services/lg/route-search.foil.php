<?php $this->layout('services/lg/layout') ?>

<?php $this->section('title') ?>
    <small>Route Search</small>
<?php $this->append() ?>

<?php $this->section('content') ?>

    <div class="card col-sm-12">
        <div class="card-body">
            <form class="form-horizontal">
                <div class="form-group row">
                    <label for="net" class="col-lg-3 col-md-4 col-sm-5 control-label">IP Address/Prefix</label>
                    <div class="col-lg-5 col-sm-6">
                        <input type="text" class="form-control" id="net" placeholder="192.0.2.0/24 | 2001:db8:7:2::/64">
                    </div>
                </div>


                <fieldset class="form-group">
                    <div class="row">
                        <legend class="col-form-label col-lg-3 col-md-4 col-sm-5"> </legend>
                        <div class="col-lg-5 col-md-6 col-sm-5">
                            <div class="form-check">
                                <label class="radio-inline" class="control-label">
                                    <input type="radio" name="sourceSelector" id="sourceSelector-table" value="table" checked="checked"> Lookup table
                                </label>
                            </div>
                            <div class="form-check">
                                <label class="radio-inline" class="col-sm-10 col-sm-offset-2 control-label">
                                    <input type="radio" name="sourceSelector" id="sourceSelector-protocol" value="protocol"> Lookup protocol
                                </label>
                            </div>
                        </div>
                    </div>
                </fieldset>


                <div class="form-group row">
                    <label for="source" class="col-lg-3 col-md-4 col-sm-5 control-label">Source</label>
                    <div class="col-lg-5 col-sm-6">
                        <select class="form-control chzn-select" id="source">
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="bg-light p-4 mt-4 shadow-sm text-center col-lg-12">
                        <button id="submit" type="button" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>



    <div class="modal fade" id="route-modal" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
            </div>
        </div>
    </div>


<?php $this->append() ?>

<?php $this->section('scripts') ?>

<script type="text/javascript">


let tables    = <?= json_encode($t->content->symbols->{'routing table'}) ?>.sort();
let protocols = <?= json_encode($t->content->symbols->protocol) ?>.sort();
let source    = 'table';

$("#submit").on( 'click', function(){
    let net     = $("#net").val().trim();
    let masklen = 32;
    if( net == "" ) {
        return;
    }
    $("#submit").prop('disabled', true);

    if( net.indexOf('/') != -1 ) {
        masklen = net.substring( net.indexOf('/') + 1);
        net     = net.substring( 0, net.indexOf('/') );
    } else if( net.indexOf(':') != -1 ) {
        masklen = 128;
    }

    $.get('<?= url('lg/' . $t->lg->router()->handle()  . '/route') ?>/' + encodeURIComponent(net) + '/' +
            encodeURIComponent(masklen) + '/' +
            source + '/' + encodeURIComponent( $("#source").val() ), function(html) {
        $('#route-modal .modal-content').html(html);
        $('#route-modal').modal('show', {backdrop: 'static'});
     });

    $("#submit").prop('disabled', false);
});

$('input:radio[name="sourceSelector"]').change( function(){
    if( $(this).is(':checked') ) {
        if( $(this).val() == "table" ) {
            source = 'table'
            $("#source").html("");
            tables.forEach( function(e){
                $("#source").append( $("<option></option>")
                    .attr("value",e)
                    .text(e)
                );
            });
            $("#source").val('master<?= $t->lg->router()->protocol() ?>');
        } else {
            source = 'protocol'
            $("#source").html("");
            protocols.forEach( function(e){
                $("#source").append( $("<option></option>")
                    .attr("value",e)
                    .text(e)
                );
            });
        }
    }
});

$(document).ready(function() {

    tables.forEach( function(e){
        $("#source").append( $("<option></option>")
            .attr("value",e)
            .text(e)
        );
    });
    $("#source").val('master<?= $t->lg->router()->protocol() ?>');
});

</script>

<?php $this->append() ?>
