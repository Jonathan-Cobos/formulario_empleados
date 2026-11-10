package com.example.demo_formulario.controller;

import java.util.List;

import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import com.example.demo_formulario.model.Empleado;
import com.example.demo_formulario.service.EmpleadoService;

@RestController
@RequestMapping("/api/empleados")
public class EmpleadoController {

    private final EmpleadoService service;

    public EmpleadoController(EmpleadoService service) {
        this.service = service;
    }

    @GetMapping
    public List<Empleado> findAll() {
        return service.findAll();
    }

    @GetMapping("/{id}")
    public Empleado findById(@PathVariable Integer id) {
        return service.findById(id);
    }

    @PostMapping
    public Empleado create(@RequestBody Empleado empleado) {
        empleado.setId(null);
        return service.save(empleado);
    }

    @PutMapping("/{id}")
    public Empleado update(@PathVariable Integer id, @RequestBody Empleado empleado) {
        service.findById(id);
        empleado.setId(id);
        return service.save(empleado);
    }

    @DeleteMapping("/{id}")
    public void delete(@PathVariable Integer id) {
        service.delete(id);
    }
}
