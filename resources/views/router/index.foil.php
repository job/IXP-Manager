<?php
    /** @var Foil\Template\Template $t */

    $this->layout( 'layouts/ixpv4' );
?>

<?php $this->section( 'title' ) ?>
    Router
<?php $this->append() ?>

<?php $this->section( 'page-header-preamble' ) ?>
    <li class="pull-right">
        <div class="btn-group btn-group-xs" role="group">
            <a type="button" class="btn btn-default" href="<?= url('router/add') ?>">
                <span class="glyphicon glyphicon-plus"></span>
            </a>
        </div>
    </li>
<?php $this->append() ?>

<?php $this->section('content') ?>

    <?= $t->alerts() ?>
    <table id='router-list' class="table">
        <thead>
        <tr>
            <td>
                Handle
            </td>
            <td>
                Name
            </td>
            <td>
                Vlan
            </td>
            <td>
                Protocol
            </td>
            <td>
                Type
            </td>
            <td>
                Router
            </td>
            <td>
                Peering IP
            </td>
            <td>
                ASN
            </td>
            <td>
                Last Updated
            </td>
            <td>
                Action
            </td>
        </tr>
        <thead>
        <tbody>
            <?php foreach( $t->routers as $router ):
                /** @var Entities\Router $router */ ?>
                <tr>
                    <td>
                        <?= $router->getHandle() ?>
                    </td>
                    <td>
                        <?= $router->getShortName() ?>
                    </td>
                    <td>
                        <a href="<?= url( '/vlan/view/id/' ).'/'.$router->getVlan()->getId()?> ">
                            <?= $router->getVlan()->getName() ?>
                        </a>
                    </td>
                    <td>
                        <?= $router->resolveProtocol() ?>
                    </td>
                    <td>
                        <?= $router->resolveTypeShortName() ?>
                    </td>
                    <td>
                        <?= $router->getRouterId() ?>
                    </td>
                    <td>
                        <?= $router->getPeeringIp() ?>
                    </td>
                    <td>
                        <?= $router->getAsn() ?>
                    </td>
                    <td>
                        <?= $router->getLastUpdated() ? $router->getLastUpdated()->format('Y-m-d H:i:s') : '(unknown)' ?>
                        <?php if( $router->getLastUpdated() && $router->lastUpdatedGreaterThanSeconds( 86400 ) ): ?>
                            <span class="label label-danger"><i class="glyphicon glyphicon-exclamation-sign" title="Last updated more than 1 day ago"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <a target="_blank" class="btn btn btn-default" href="<?= route('apiv4-router-gen-config', [ 'handle' => $router->getHandle() ] ) ?>" title="Configuration">
                                <i class="glyphicon glyphicon-file"></i>
                            </a>
                            <a class="btn btn btn-default" href="<?= url('router/view/').'/'.$router->getId() ?>" title="Preview">
                                <i class="glyphicon glyphicon-eye-open"></i>
                            </a>
                            <a class="btn btn btn-default" href="<?= url('router/edit/').'/'.$router->getId() ?>" title="Edit">
                                <i class="glyphicon glyphicon-pencil"></i>
                            </a>
                            <a class="btn btn btn-default" id="delete-router-<?=$router->getId() ?>" href="" title="Delete">
                                <i class="glyphicon glyphicon-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach;?>
        <tbody>
    </table>

<?php $this->append() ?>

<?php $this->section( 'scripts' ) ?>
    <script>
        $('#router-list').DataTable({
            "autoWidth": false
        });

        $( "a[id|='delete-router']" ).on( 'click', function(e){
            e.preventDefault();
            var rtid = (this.id).substring(14);
            bootbox.confirm({
                message: "Do you want to delete this router ?",
                buttons: {
                    confirm: {
                        label: 'Confirm',
                        className: 'btn-primary',
                    },
                    cancel: {
                        label: 'Cancel',
                        className: 'btn-default',
                    }
                },
                callback: function (result) {
                    if(result){
                        location.href = "<?= url('router/delete')?>/"+rtid;
                    }
                    else{
                        $('.bootbox.modal').modal('hide');
                    }
                }
            });
        });

    </script>
<?php $this->append() ?>