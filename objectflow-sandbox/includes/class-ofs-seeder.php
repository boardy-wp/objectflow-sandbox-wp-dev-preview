<?php
if (!defined('ABSPATH')) { exit; }

class OFS_Seeder {
    public static function reset_and_seed(): void {
        global $wpdb;
        $t = OFS_DB::table_names();
        $wpdb->query("DELETE FROM {$t['events']}");
        $wpdb->query("DELETE FROM {$t['submissions']}");
        $wpdb->query("DELETE FROM {$t['cases']}");
        $wpdb->query("DELETE FROM {$t['objects']}");
        $wpdb->query("DELETE FROM {$t['workflows']}");
        self::seed_if_empty();
    }

    public static function seed_if_empty(): void {
        global $wpdb;
        $t = OFS_DB::table_names();
        $count=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$t['workflows']}");
        if ($count>0) return;
        $now=current_time('mysql');
        $def=self::workflow_definition();
        $wpdb->insert($t['workflows'],['workflow_key'=>'equipment_refurbishment_demo','name'=>'Equipment Refurbishment Demo Workflow','version'=>1,'definition'=>wp_json_encode($def),'is_active'=>1,'created_at'=>$now,'updated_at'=>$now]);
        $wid=(int)$wpdb->insert_id;
        $states=['waiting_for_test','in_testing','needs_repair','in_repair','waiting_for_qc','ready_for_dispatch'];
        for($i=1;$i<=8;$i++){
            $sn=sprintf('SN-DEMO-%04d',$i);
            $wpdb->insert($t['objects'],['serial_number'=>$sn,'object_type'=>'Equipment','manufacturer'=>'DemoCo','model'=>'Model '.$i,'created_at'=>$now,'updated_at'=>$now]);
            $oid=(int)$wpdb->insert_id;
            if($i===7){
                self::insert_case($oid,$wid,'CASE-DEMO-0007','dispatched',0,$now);
                continue;
            }
            if($i===8){
                self::insert_case($oid,$wid,'CASE-DEMO-0008','scrapped',0,$now);
                continue;
            }
            self::insert_case($oid,$wid,sprintf('CASE-DEMO-%04d',$i),$states[$i-1],1,$now);
        }
    }

    private static function insert_case(int $object_id,int $workflow_id,string $case_number,string $node,int $active,string $now): void {
        global $wpdb; $t=OFS_DB::table_names();
        $wpdb->insert($t['cases'],['object_id'=>$object_id,'workflow_id'=>$workflow_id,'case_number'=>$case_number,'current_node_id'=>$node,'current_status'=>$node,'current_location'=>'Demo Lab','responsible_role'=>'technician','is_active'=>$active,'opened_at'=>$now,'closed_at'=>$active?null:$now,'created_by'=>get_current_user_id()?:null,'created_at'=>$now,'updated_at'=>$now]);
        $cid=(int)$wpdb->insert_id;
        $wpdb->insert($t['events'],['object_id'=>$object_id,'case_id'=>$cid,'event_type'=>'case_created','to_node_id'=>$node,'to_status'=>$node,'to_location'=>'Demo Lab','message'=>'Demo case seeded','created_at'=>$now]);
    }

    private static function workflow_definition(): array { return [
        'schemaVersion'=>'0.1','key'=>'equipment_refurbishment_demo','name'=>'Equipment Refurbishment Demo Workflow','version'=>1,'startNode'=>'intake','closedNodes'=>['dispatched','scrapped'],'roles'=>['intake','technician','qc','logistics','manager'],
        'nodes'=>[
            self::node('intake','Intake','intake','intake','Receiving',[self::form('intake_form',[self::btn('submit','Move to Test Queue','waiting_for_test')])]),
            self::node('waiting_for_test','Waiting for Test','waiting_for_test','technician','Test Queue',[]),
            self::node('in_testing','In Testing','in_testing','technician','Test Bench',[self::form('test_result_form',[self::btn('to_qc','Pass to QC','waiting_for_qc'),self::btn('needs_repair','Needs Repair','needs_repair'),self::btn('retest','Retest','in_testing'),self::btn('scrap','Scrap','scrapped')])]),
            self::node('needs_repair','Needs Repair','needs_repair','technician','Repair Queue',[]),
            self::node('in_repair','In Repair','in_repair','technician','Repair Bench',[self::form('repair_form',[self::btn('to_qc','Repair Complete','waiting_for_qc'),self::btn('continue','Continue Repair','in_repair'),self::btn('scrap','Scrap','scrapped')])]),
            self::node('waiting_for_qc','Waiting for QC','waiting_for_qc','qc','QC Queue',[]),
            self::node('in_qc','In QC','in_qc','qc','QC Station',[self::form('qc_form',[self::btn('ready_dispatch','Ready for Dispatch','ready_for_dispatch'),self::btn('back_repair','Back to Repair','needs_repair'),self::btn('scrap','Scrap','scrapped')])]),
            self::node('ready_for_dispatch','Ready for Dispatch','ready_for_dispatch','logistics','Dispatch Area',[self::form('dispatch_form',[self::btn('dispatch','Dispatch','dispatched')])]),
            self::node('dispatched','Dispatched','dispatched','logistics','Off Site',[]),
            self::node('scrapped','Scrapped','scrapped','manager','Scrap Yard',[]),
        ]]; }
    private static function node($id,$label,$status,$role,$loc,$forms){return ['id'=>$id,'type'=>'container','label'=>$label,'status'=>$status,'responsibleRole'=>$role,'defaultLocation'=>$loc,'forms'=>$forms];}
    private static function form($id,$buttons){return ['id'=>$id,'buttons'=>$buttons];}
    private static function btn($id,$label,$target){return ['id'=>$id,'label'=>$label,'targetNode'=>$target];}
}
