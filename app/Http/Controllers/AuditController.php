<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Audit;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$audit = Audit::orderBy('id','desc')->get();
        // return $audit;

         $auditUser = DB::select("select audits.* ,CONCAT(usuario.nombres, ' ', usuario.apellidos) As nombres, usuario.id_group, sys_group_users.level
            from audits ,usuario ,sys_group_users
            where audits.user_id  = usuario.idusuario 
            and  usuario.id_group = sys_group_users.id
            and audits.auditable_type  LIKE '%Usuario%'
            ORDER BY id DESC

         ");

         $auditCurso = DB::select("select audits.* ,CONCAT(usuario.nombres, ' ', usuario.apellidos) As nombres, usuario.id_group, sys_group_users.level
         from audits ,usuario ,sys_group_users
         where audits.user_id  = usuario.idusuario 
         and  usuario.id_group = sys_group_users.id
         and audits.auditable_type  LIKE '%Curso%'
         ORDER BY id DESC

          ");
     

        $auditCodigosLibros = DB::select("select audits.* ,CONCAT(usuario.nombres, ' ', usuario.apellidos) As nombres, usuario.id_group, sys_group_users.level
        from audits ,usuario ,sys_group_users
        where audits.user_id  = usuario.idusuario 
        and  usuario.id_group = sys_group_users.id
        and audits.auditable_type  LIKE '%CodigosLibros%'
        ORDER BY id DESC

            ");
    
        $auditTemporada = DB::select("select audits.* ,CONCAT(usuario.nombres, ' ', usuario.apellidos) As nombres, usuario.id_group, sys_group_users.level
        from audits ,usuario ,sys_group_users
        where audits.user_id  = usuario.idusuario 
        and  usuario.id_group = sys_group_users.id
        and audits.auditable_type  LIKE '%Temporada%'
        ORDER BY id DESC

        ");
       
       $auditInstitucion =  DB::select("select audits.* ,CONCAT(usuario.nombres, ' ', usuario.apellidos) As nombres, usuario.id_group, sys_group_users.level
       from audits ,usuario ,sys_group_users
       where audits.user_id  = usuario.idusuario 
       and  usuario.id_group = sys_group_users.id
       and audits.auditable_type  LIKE '%Institucion%'
       ORDER BY id DESC
  
         ");
      

         return [
            "auditUser" => $auditUser,
            "auditCurso" => $auditCurso,
            "auditCodigosLibros" => $auditCodigosLibros,
            "auditTemporada" => $auditTemporada,
            "auditInstitucion" => $auditInstitucion

         ];
    }

    public function eliminarAudit(Request $request){
     

        $id = $request->get('id');
        Audit::findOrFail($id)->delete();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
