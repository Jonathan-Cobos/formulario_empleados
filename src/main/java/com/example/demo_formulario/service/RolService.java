package com.example.demo_formulario.service;

import java.util.List;

import org.springframework.http.HttpStatus;
import org.springframework.stereotype.Service;
import org.springframework.web.server.ResponseStatusException;

import com.example.demo_formulario.model.Rol;
import com.example.demo_formulario.repository.RolRepository;

@Service
public class RolService {

    private final RolRepository repository;

    public RolService(RolRepository repository) {
        this.repository = repository;
    }

    public List<Rol> findAll() {
        return repository.findAll();
    }

    public Rol findById(Integer id) {
        return repository.findById(id)
                .orElseThrow(() -> new ResponseStatusException(HttpStatus.NOT_FOUND, "Rol no encontrado"));
    }

    public Rol save(Rol rol) {
        return repository.save(rol);
    }

    public void delete(Integer id) {
        repository.delete(findById(id));
    }
}
