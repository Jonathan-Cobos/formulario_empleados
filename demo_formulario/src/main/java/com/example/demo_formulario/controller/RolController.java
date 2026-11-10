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

import com.example.demo_formulario.model.Rol;
import com.example.demo_formulario.service.RolService;

@RestController
@RequestMapping("/api/roles")
public class RolController {

    private final RolService service;

    public RolController(RolService service) {
        this.service = service;
    }

    @GetMapping
    public List<Rol> findAll() {
        return service.findAll();
    }

    @GetMapping("/{id}")
    public Rol findById(@PathVariable Integer id) {
        return service.findById(id);
    }

    @PostMapping
    public Rol create(@RequestBody Rol rol) {
        rol.setId(null);
        return service.save(rol);
    }

    @PutMapping("/{id}")
    public Rol update(@PathVariable Integer id, @RequestBody Rol rol) {
        service.findById(id);
        rol.setId(id);
        return service.save(rol);
    }

    @DeleteMapping("/{id}")
    public void delete(@PathVariable Integer id) {
        service.delete(id);
    }
}
